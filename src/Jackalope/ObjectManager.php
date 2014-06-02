<?php
namespace Jackalope;

use ArrayIterator;
use InvalidArgumentException;

use Jackalope\Transport\NodeTypeFilterInterface;
use PHPCR\SessionInterface;
use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use PHPCR\RepositoryException;
use PHPCR\AccessDeniedException;
use PHPCR\ItemNotFoundException;
use PHPCR\ItemExistsException;
use PHPCR\PathNotFoundException;
use PHPCR\UnsupportedRepositoryOperationException;

use PHPCR\Util\CND\Writer\CndWriter;
use PHPCR\Version\VersionInterface;

use PHPCR\Util\PathHelper;
use PHPCR\Util\CND\Parser\CndParser;

use Jackalope\Transport\Operation;
use Jackalope\Transport\TransportInterface;
use Jackalope\Transport\PermissionInterface;
use Jackalope\Transport\WritingInterface;
use Jackalope\Transport\NodeTypeManagementInterface;
use Jackalope\Transport\NodeTypeCndManagementInterface;
use Jackalope\Transport\AddNodeOperation;
use Jackalope\Transport\MoveNodeOperation;
use Jackalope\Transport\RemoveNodeOperation;
use Jackalope\Transport\RemovePropertyOperation;

/**
 * Implementation specific class that talks to the Transport layer to get nodes
 * and caches every node retrieved to improve performance.
 *
 * For write operations, the object manager acts as the Unit of Work handler:
 * it keeps track which nodes are dirty and updates them with the transport
 * interface.
 *
 * As not all transports have the same capabilities, we do some checks here,
 * but only if the check is not already done at the entry point. For
 * versioning, transactions, locking and so on, the check is done when the
 * respective manager is requested from the session or workspace. As those
 * managers are the only entry points we do not check here again.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @private
 */
class ObjectManager
{
    /**
     * The factory to instantiate objects
     * @var FactoryInterface
     */
    protected $factory;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var TransportInterface
     */
    protected $transport;

    /**
     * Mapping of typename => absolutePath => node or item object.
     *
     * There is no notion of order here. The order is defined by order in the
     * Node::nodes array.
     *
     * @var array
     */
    protected $objectsByPath = array('Node' => array());

    /**
     * Mapping of uuid => absolutePath.
     *
     * Take care never to put a path in here unless there is a node for that
     * path in objectsByPath.
     *
     * @var array
     */
    protected $objectsByUuid = array();

    /**
     * This is an ordered list of all operations to commit to the transport
     * during save. The values are the add, move and remove operation classes.
     *
     * Add, remove and move actions need to be saved in the correct order to avoid
     * i.e. adding something where a node has not yet been moved to.
     *
     * @var \Jackalope\Transport\Operation[]
     */
    protected $operationsLog = array();

    /**
     * Contains the list of paths that have been added to the workspace in the
     * current session.
     *
     * Keys are the full paths to be added
     *
     * @var AddNodeOperation[]
     */
    protected $nodesAdd = array();

    /**
     * Contains the list of node remove operations for the current session.
     *
     * Keys are the full paths to be removed.
     *
     * Note: Keep in mind that a delete is recursive, but we only have the
     * explicitly deleted paths in this array. We check on deleted parents
     * whenever retrieving a non-cached node.
     *
     * @var RemoveNodeOperation[]
     */
    protected $nodesRemove = array();

    /**
     * Contains the list of property remove operations for the current session.
     *
     * Keys are the full paths of properties to be removed.
     *
     * @var RemovePropertyOperation[]
     */
    protected $propertiesRemove = array();

    /**
     * Contains a list of nodes that where moved during this session.
     *
     * Keys are the source paths, values the move operations containing the
     * target path.
     *
     * The objectsByPath array is updated immediately and any getItem and
     * similar requests are rewritten for the transport layer until save()
     *
     * Only nodes can be moved, not properties.
     *
     * Note: Keep in mind that moving also affects all children of the moved
     * node, but we only have the explicitly moved paths in this array. We
     * check on moved parents whenever retrieving a non-cached node.
     *
     * @var MoveNodeOperation[]
     */
    protected $nodesMove = array();

    /**
     * Create the ObjectManager instance with associated session and transport
     *
     * @param FactoryInterface   $factory   the object factory
     * @param TransportInterface $transport
     * @param SessionInterface   $session
     */
    public function __construct(FactoryInterface $factory, TransportInterface $transport, SessionInterface $session)
    {
        $this->factory = $factory;
        $this->transport = $transport;
        $this->session = $session;
    }

    /**
     * Get the node identified by an absolute path.
     *
     * To prevent unnecessary work to be done a cache is filled to only fetch
     * nodes once. To reset a node with the data from the backend, use
     * Node::refresh()
     *
     * Uses the factory to create a Node object.
     *
     * @param string $absPath The absolute path of the node to fetch.
     * @param string $class   The class of node to get. TODO: Is it sane to fetch
     *      data separately for Version and normal Node?
     * @param object $object A (prefetched) object (de-serialized json) from the backend
     *      only to be used if we get child nodes in one backend call
     *
     * @return NodeInterface
     *
     * @throws ItemNotFoundException If nothing is found at that
     *      absolute path
     * @throws RepositoryException If the path is not absolute or not
     *      well-formed
     *
     * @see Session::getNode()
     */
    public function getNodeByPath($absPath, $class = 'Node', $object = null)
    {
        $absPath = PathHelper::normalizePath($absPath);

        if (!empty($this->objectsByPath[$class][$absPath])) {
            // Return it from memory if we already have it
            return $this->objectsByPath[$class][$absPath];
        }

        // do this even if we have item in cache, will throw error if path is deleted - sanity check
        $fetchPath = $this->getFetchPath($absPath, $class);
        if (!$object) {
            // this is the first request, get data from transport
            $object = $this->transport->getNode($fetchPath);
        }

        // recursively create nodes for pre-fetched children if fetchDepth was > 1
        foreach ($object as $name => $properties) {
            if (is_object($properties)) {
                $objVars = get_object_vars($properties);
                $countObjVars = count($objVars);
                // if there's more than one objectvar or just one and this isn't jcr:uuid,
                // then we assume this child was pre-fetched from the backend completely
                if ($countObjVars > 1 || ($countObjVars == 1 && !isset($objVars['jcr:uuid']))) {
                    try {
                        $parentPath = ('/' === $absPath) ? '/' : $absPath . '/';
                        $this->getNodeByPath($parentPath . $name, $class, $properties);
                    } catch (ItemNotFoundException $ignore) {
                        // we get here if the item was deleted or moved locally. just ignore
                    }
                }
            }
        }

        /** @var $node NodeInterface */
        $node = $this->factory->get(
            $class,
            array(
                $object,
                $absPath,
                $this->session,
                $this
            )
        );
        if ($uuid = $node->getIdentifier()) {
            // map even nodes that are not mix:referenceable, as long as they have a uuid
            $this->objectsByUuid[$uuid] = $absPath;
        }
        $this->objectsByPath[$class][$absPath] = $node;

        return $this->objectsByPath[$class][$absPath];
    }

    /**
     * Get multiple nodes identified by an absolute paths. Missing nodes are
     * ignored.
     *
     * Note paths that cannot be found will be ignored and missing from the
     * result.
     *
     * Uses the factory to create Node objects.
     *
     * @param array $absPaths Array containing the absolute paths of the nodes to
     *      fetch.
     * @param string $class The class of node to get. TODO: Is it sane to
     *      fetch data separately for Version and normal Node?
     * @param array|null $typeFilter Node type list to skip some nodes
     *
     * @return Node[] Iterator that contains all found NodeInterface
     *      instances keyed by their path
     *
     * @throws RepositoryException If the path is not absolute or not
     *      well-formed
     *
     * @see Session::getNodes()
     */
    public function getNodesByPath($absPaths, $class = 'Node', $typeFilter = null)
    {
        $nodesPathIterator = new NodePathIterator(
            $this, $absPaths, $class, $typeFilter
        );

        return $nodesPathIterator;
    }

    public function getNodesByPathAsArray($paths, $class = 'Node', $typeFilter = null)
    {
        if (is_string($typeFilter)) {
            $typeFilter = array($typeFilter);
        }
        $nodes = $fetchPaths = array();

        foreach ($paths as $absPath) {
            if (!empty($this->objectsByPath[$class][$absPath])) {
                // Return it from memory if we already have it and type is correct
                if ($typeFilter
                    && !$this->matchNodeType($this->objectsByPath[$class][$absPath], $typeFilter)
                ) {
                    // skip this node if it did not match a type filter
                    continue;
                }
                $nodes[$absPath] = $this->objectsByPath[$class][$absPath];
            } else {
                $nodes[$absPath] = '';
                $fetchPaths[$absPath] = $this->getFetchPath($absPath, $class);
            }
        }

        $userlandTypeFilter = false;
        if (!empty($fetchPaths)) {
            if ($typeFilter) {
                if ($this->transport instanceof NodeTypeFilterInterface) {
                    $data = $this->transport->getNodesFiltered($fetchPaths, $typeFilter);
                } else {
                    $data = $this->transport->getNodes($fetchPaths);
                    $userlandTypeFilter = true;
                }
            } else {
                $data = $this->transport->getNodes($fetchPaths);
            }

            $inversePaths = array_flip($fetchPaths);

            foreach ($data as $fetchPath => $item) {
                // only add this node to the list if it was actually requested.
                if (isset($inversePaths[$fetchPath]) &&
                    (!$userlandTypeFilter || $this->matchNodeType($item, $typeFilter))
                ) {
                    // transform back to session paths from the fetch paths, in case of
                    // a pending move operation
                    $absPath = $inversePaths[$fetchPath];

                    $nodes[$absPath] = $this->getNodeByPath($absPath, $class, $item);
                    unset($inversePaths[$fetchPath]);
                } else {
                    // this is either a prefetched node that was not requested
                    // or it falls through the type filter. cache it.

                    // first undo eventual move operation
                    $parent = $fetchPath;
                    $relPath = '';
                    while ($parent) {
                        if (isset($inversePaths[$parent])) {
                            break;
                        }
                        if ('/' == $parent) {
                            $parent = false;
                        } else {
                            $parent = PathHelper::getParentPath($parent);
                            $relPath = '/' . PathHelper::getNodeName($parent) . $relPath;
                        }
                    }
                    if ($parent) {
                        $this->getNodeByPath($parent . $relPath, $class, $item);
                    }
                }
            }

            // clean away the not found paths from the final result
            foreach ($inversePaths as $absPath) {
                unset($nodes[$absPath]);
            }
        }

        return $nodes;
    }

    /**
     * Check if a node is of any of the types listed in typeFilter.
     *
     * @param NodeInterface $node
     * @param array         $typeFilter
     *
     * @return boolean
     */
    private function matchNodeType(NodeInterface $node, array $typeFilter)
    {
        foreach ($typeFilter as $type) {
            if ($node->isNodeType($type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * This method will either let the transport filter if that is possible or
     * forward to getNodes and return the names of the nodes found there.,
     *
     * @param NodeInterface $node
     * @param string|array  $nameFilter
     * @param string|array  $typeFilter
     *
     * @return ArrayIterator
     */
    public function filterChildNodeNamesByType(NodeInterface $node, $nameFilter, $typeFilter)
    {
        if ($this->transport instanceof NodeTypeFilterInterface) {
            return $this->transport->filterChildNodeNamesByType($node->getPath(), $node->getNodeNames($nameFilter), $typeFilter);
        }

        // fallback: get the actual nodes and let that filter. this is expensive.
        return new ArrayIterator(array_keys($node->getNodes($nameFilter, $typeFilter)->getArrayCopy()));
    }

    /**
     * Resolve the path through all pending operations and sanity check while
     * doing this.
     *
     * @param string $absPath The absolute path of the node to fetch.
     * @param string $class   The class of node to get. TODO: Is it sane to fetch
     *      data separately for Version and normal Node?
     *
     * @return string fetch path
     *
     * @throws ItemNotFoundException if while walking backwards through the
     *      operations log we see this path was moved away or got deleted
     */
    protected function getFetchPath($absPath, $class)
    {
        $absPath = PathHelper::normalizePath($absPath);

        if (!isset($this->objectsByPath[$class])) {
            $this->objectsByPath[$class] = array();
        }

        $op = end($this->operationsLog);
        while ($op) {
            if ($op instanceof MoveNodeOperation) {
                if ($absPath == $op->srcPath) {
                    throw new ItemNotFoundException("Path not found (moved in current session): $absPath");
                }
                if (strpos($absPath, $op->srcPath . '/') === 0) {
                    throw new ItemNotFoundException("Path not found (parent node {$op->srcPath} moved in current session): $absPath");
                }
                if (strpos($absPath, $op->dstPath . '/') === 0 || $absPath == $op->dstPath) {
                    $absPath= substr_replace($absPath, $op->srcPath, 0, strlen($op->dstPath));
                }
            } elseif ($op instanceof RemoveNodeOperation || $op instanceof RemovePropertyOperation) {
                if ($absPath == $op->srcPath) {
                    throw new ItemNotFoundException("Path not found (node deleted in current session): $absPath");
                }
                if (strpos($absPath, $op->srcPath . '/') === 0) {
                    throw new ItemNotFoundException("Path not found (parent node {$op->srcPath} deleted in current session): $absPath");
                }
            } elseif ($op instanceof AddNodeOperation) {
                if ($absPath == $op->srcPath) {
                    // we added this node at this point so no more sanity checks needed.
                    return $absPath;
                }
            }

            $op = prev($this->operationsLog);
        }

        return $absPath;
    }

    /**
     * Get the property identified by an absolute path.
     *
     * Uses the factory to instantiate a Property.
     *
     * Currently Jackalope just loads the containing node and then returns
     * the requested property of the node instance.
     *
     * @param  string            $absPath The absolute path of the property to create.
     * @return PropertyInterface
     *
     * @throws ItemNotFoundException if item is not found at this path
     */
    public function getPropertyByPath($absPath)
    {
        list($name, $nodep) = $this->getNodePath($absPath);
        // OPTIMIZE: should use transport->getProperty - when we implement this, we must make sure only one instance of each property ever exists. and do the moved/deleted checks that are done in node
        $n = $this->getNodeByPath($nodep);
        try {
            return $n->getProperty($name); //throws PathNotFoundException if there is no such property
        } catch (PathNotFoundException $e) {
            throw new ItemNotFoundException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get all nodes of those properties in one batch, then collect the
     * properties of them.
     *
     * @param $absPaths
     *
     * @return ArrayIterator that contains all found PropertyInterface
     *      instances keyed by their path
     */
    public function getPropertiesByPath($absPaths)
    {
        // list of nodes to fetch
        $nodemap = array();
        // ordered list of what to return
        $returnmap = array();

        foreach ($absPaths as $path) {
            list($name, $nodep) = $this->getNodePath($path);
            if (! isset($nodemap[$nodep])) {
                $nodemap[$nodep] = $nodep;
            }
            $returnmap[$path] = array('name' => $name, 'path' => $nodep);
        }
        $nodes = $this->getNodesByPath($nodemap);

        $properties = array();
        foreach ($returnmap as $key => $data) {
            if (isset($nodes[$data['path']]) && $nodes[$data['path']]->hasProperty($data['name'])) {
                $properties[$key] = $nodes[$data['path']]->getProperty($data['name']);
            }
        }

        return new ArrayIterator($properties);
    }

    /**
     * Get the node path for a property, and the property name
     *
     * @param $absPath
     *
     * @return array with name, node path
     */
    protected function getNodePath($absPath)
    {
        $absPath = PathHelper::normalizePath($absPath);

        $name = PathHelper::getNodeName($absPath); //the property name
        $nodep = PathHelper::getParentPath($absPath,0,strrpos($absPath,'/')+1); //the node this property should be in

        return array($name, $nodep);
    }

    /**
     * Get the node identified by a relative path.
     *
     * If you have an absolute path use {@link getNodeByPath()} for better
     * performance.
     *
     * @param string $relPath relative path
     * @param string $context context path
     * @param string $class   optional class name for the factory
     *
     * @return NodeInterface The specified Node. if not available,
     *      ItemNotFoundException is thrown
     *
     * @throws ItemNotFoundException If the path was not found
     * @throws RepositoryException   if another error occurs.
     *
     * @see Session::getNode()
     */
    public function getNode($relPath, $context, $class = 'Node')
    {
        $path = PathHelper::absolutizePath($relPath, $context);

        return $this->getNodeByPath($path, $class);
    }

    /**
     * Get the node identified by an uuid.
     *
     * @param string $identifier uuid
     * @param string $class      optional class name for factory
     *
     * @return NodeInterface The specified Node. if not available,
     *      ItemNotFoundException is thrown
     *
     * @throws ItemNotFoundException If the path was not found
     * @throws RepositoryException   if another error occurs.
     *
     * @see Session::getNodeByIdentifier()
     */
    public function getNodeByIdentifier($identifier, $class = 'Node')
    {
        if (empty($this->objectsByUuid[$identifier])) {
            $data = $this->transport->getNodeByIdentifier($identifier);
            $path = $data->{':jcr:path'};
            unset($data->{':jcr:path'});
            // TODO: $path is a backend path. we should inverse the getFetchPath operation here
            $node = $this->getNodeByPath($path, $class, $data);
            $this->objectsByUuid[$identifier] = $path; //only do this once the getNodeByPath has worked

            return $node;
        }

        return $this->getNodeByPath($this->objectsByUuid[$identifier], $class);
    }

    /**
     * Get the nodes identified by the given uuids
     *
     * Note uuids that are not found will be ignored
     *
     * @param array  $identifiers uuid
     * @param string $class       optional class name for the factory
     *
     * @return Node[] Iterator of the specified nodes keyed by their unique ids
     *
     * @throws RepositoryException if another error occurs.
     *
     * @see Session::getNodesByIdentifier()
     */
    public function getNodesByIdentifier($identifiers, $class = 'Node')
    {
        $nodes = $fetchIdentifiers = array();

        foreach ($identifiers as $uuid) {
            if (!empty($this->objectsByUuid[$uuid])
                && !empty($this->objectsByPath[$class][$this->objectsByUuid[$uuid]])
            ) {
                // Return it from memory if we already have it
                $nodes[$uuid] = $this->objectsByPath[$class][$this->objectsByUuid[$uuid]];
            } else {
                $fetchPaths[$uuid] = $uuid;
            }
        }

        if (!empty($fetchPaths)) {
            $data = $this->transport->getNodesByIdentifier($fetchPaths);

            foreach ($data as $absPath => $item) {
                // TODO: $absPath is the backend path. we should inverse the getFetchPath operation here
                // build the node from the received data
                $node = $this->getNodeByPath($absPath, $class, $item);
                $nodes[$node->getIdentifier()] = $node;
            }
        }

        return new ArrayIterator($nodes);
    }

    /**
     * Retrieves the stream for a binary value.
     *
     * @param string $path The absolute path to the stream
     *
     * @return stream
     */
    public function getBinaryStream($path)
    {
        return $this->transport->getBinaryStream($this->getFetchPath($path, 'Node'));
    }

    /**
     * Returns the node types specified by name in the array or all types if no
     * filter is given.
     *
     * This is only a proxy to the transport
     *
     * @param array $nodeTypes Empty for all or specify node types by name
     *
     * @return \DOMDocument containing the nodetype information
     */
    public function getNodeTypes($nodeTypes = array())
    {
        return $this->transport->getNodeTypes($nodeTypes);
    }

    /**
     * Get a single nodetype.
     *
     * @param string $nodeType the name of nodetype to get from the transport
     *
     * @return \DOMDocument containing the nodetype information
     *
     * @see getNodeTypes()
     */
    public function getNodeType($nodeType)
    {
        return $this->getNodeTypes(array($nodeType));
    }

    /**
     * Register node types with the backend.
     *
     * This is only a proxy to the transport
     *
     * @param array   $types       an array of NodeTypeDefinitions
     * @param boolean $allowUpdate whether to fail if node already exists or to
     *      update it
     *
     * @return bool true on success
     */
    public function registerNodeTypes($types, $allowUpdate)
    {
        if ($this->transport instanceof NodeTypeManagementInterface) {
            return $this->transport->registerNodeTypes($types, $allowUpdate);
        }

        if ($this->transport instanceof NodeTypeCndManagementInterface) {
            $writer = new CndWriter($this->session->getWorkspace()->getNamespaceRegistry());

            return $this->transport->registerNodeTypesCnd($writer->writeString($types), $allowUpdate);
        }

        throw new UnsupportedRepositoryOperationException('Transport does not support registering node types');
    }

    /**
     * Returns all accessible REFERENCE properties in the workspace that point
     * to the node
     *
     * @param string $path the path of the referenced node
     * @param string $name name of referring REFERENCE properties to be
     *      returned; if null then all referring REFERENCEs are returned
     *
     * @return ArrayIterator
     *
     * @see Node::getReferences()
     */
    public function getReferences($path, $name = null)
    {
        $references = $this->transport->getReferences($this->getFetchPath($path, 'Node'), $name);

        return $this->pathArrayToPropertiesIterator($references);
    }

    /**
     * Returns all accessible WEAKREFERENCE properties in the workspace that
     * point to the node
     *
     * @param string $path the path of the referenced node
     * @param string $name name of referring WEAKREFERENCE properties to be
     *      returned; if null then all referring WEAKREFERENCEs are returned
     *
     * @return ArrayIterator
     *
     * @see Node::getWeakReferences()
     */
    public function getWeakReferences($path, $name = null)
    {
        $references = $this->transport->getWeakReferences($this->getFetchPath($path, 'Node'), $name);

        return $this->pathArrayToPropertiesIterator($references);
    }

    /**
     * Transform an array containing properties paths to an ArrayIterator over
     * Property objects
     *
     * @param  array $propertyPaths an array of properties paths
     *
     * @return ArrayIterator
     */

    protected function pathArrayToPropertiesIterator($propertyPaths)
    {
        //FIXME: this will break if we have non-persisted move
        return new ArrayIterator($this->getPropertiesByPath($propertyPaths));
    }

    /**
     * Register node types with compact node definition format
     *
     * This is only a proxy to the transport
     *
     * @param  string  $cnd         a string with cnd information
     * @param  boolean $allowUpdate whether to fail if node already exists or to update it
     * @return bool    true on success
     */
    public function registerNodeTypesCnd($cnd, $allowUpdate)
    {
        if ($this->transport instanceof NodeTypeCndManagementInterface) {
            return $this->transport->registerNodeTypesCnd($cnd, $allowUpdate);
        }

        if ($this->transport instanceof NodeTypeManagementInterface) {
            $workspace = $this->session->getWorkspace();
            $nsRegistry = $workspace->getNamespaceRegistry();

            $parser = new CndParser($workspace->getNodeTypeManager());
            $res = $parser->parseString($cnd);
            $ns = $res['namespaces'];
            $types = $res['nodeTypes'];
            foreach ($ns as $prefix => $uri) {
                $nsRegistry->registerNamespace($prefix, $uri);
            }

            return $workspace->getNodeTypeManager()->registerNodeTypes($types, $allowUpdate);
        }

        throw new UnsupportedRepositoryOperationException('Transport does not support registering node types');
    }

    /**
     * Push all recorded changes to the backend.
     *
     * The order is important to avoid conflicts
     * 1. operationsLog
     * 2. commit any other changes
     *
     * If transactions are enabled but we are not currently inside a
     * transaction, the session is responsible to start a transaction to make
     * sure the backend state does not get messed up in case of error.
     */
    public function save()
    {
        if (! $this->transport instanceof WritingInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support writing');
        }

        try {
            $this->transport->prepareSave();

            $this->executeOperations($this->operationsLog);

            // loop through cached nodes and commit all dirty and set them to clean.
            if (isset($this->objectsByPath['Node'])) {
                foreach ($this->objectsByPath['Node'] as $node) {
                    /** @var $node Node */
                    if ($node->isModified()) {
                        if (! $node instanceof NodeInterface) {
                            throw new RepositoryException('Internal Error: Unknown type '.get_class($node));
                        }
                        $this->transport->updateProperties($node);
                        if ($node->needsChildReordering()) {
                            $this->transport->reorderChildren($node);
                        }
                    }
                }
            }

            $this->transport->finishSave();
        } catch (\Exception $e) {
            $this->transport->rollbackSave();

            if (! $e instanceof RepositoryException) {
                throw new RepositoryException('Error inside the transport layer: '.$e->getMessage(), null, $e);
            }

            throw $e;
        }

        foreach ($this->operationsLog as $operation) {
            if ($operation instanceof MoveNodeOperation) {
                if (isset($this->objectsByPath['Node'][$operation->dstPath])) {
                    // might not be set if moved again afterwards
                    // move is not treated as modified, need to confirm separately
                    $this->objectsByPath['Node'][$operation->dstPath]->confirmSaved();
                }
            }
        }

        //clear those lists before reloading the newly added nodes from backend, to avoid collisions
        $this->nodesRemove = array();
        $this->propertiesRemove = array();
        $this->nodesMove = array();

        foreach ($this->operationsLog as $operation) {
            if ($operation instanceof AddNodeOperation) {
                if (! $operation->node->isDeleted()) {
                    $operation->node->confirmSaved();
                }
            }
        }

        if (isset($this->objectsByPath['Node'])) {
            foreach ($this->objectsByPath['Node'] as $item) {
                /** @var $item Item */
                if ($item->isModified() || $item->isMoved()) {
                    $item->confirmSaved();
                }
            }
        }

        $this->nodesAdd = array();
        $this->operationsLog = array();
    }

    /**
     * Execute the recorded operations in the right order, skipping
     * stale data.
     *
     * @param Operation[] $operations
     */
    protected function executeOperations(array $operations)
    {
        $lastType = null;
        $batch = array();

        foreach ($operations as $operation) {
            if ($operation->skip) {
                continue;
            }

            if (null === $lastType) {
                $lastType = $operation->type;
            }

            if ($operation->type != $lastType) {
                $this->executeBatch($lastType, $batch);
                $lastType = $operation->type;
                $batch = array();
            }

            $batch[] = $operation;
        }

        // only execute last batch if not all was skipped
        if (! count($batch)) {
            return;
        }

        $this->executeBatch($lastType, $batch);

    }

    /**
     * Execute a batch of operations of one type.
     *
     * @param int $type               type of the operations to be executed
     * @param Operation[] $operations list of same type operations
     *
     * @throws \Exception
     */
    protected function executeBatch($type, $operations)
    {
        switch ($type) {
            case Operation::ADD_NODE:
                $this->transport->storeNodes($operations);
                break;
            case Operation::MOVE_NODE:
                $this->transport->moveNodes($operations);
                break;
            case Operation::REMOVE_NODE:
                $this->transport->deleteNodes($operations);
                break;
            case Operation::REMOVE_PROPERTY:
                $this->transport->deleteProperties($operations);
                break;
            default:
                throw new \Exception('internal error: unknown operation "' . $type . '"');
        }
    }

    /**
     * Removes the cache of the predecessor version after the node has been
     * checked in.
     *
     * TODO: document more clearly
     *
     * @see VersionManager::checkin
     *
     * @return VersionInterface node version
     */
    public function checkin($absPath)
    {
        $path = $this->transport->checkinItem($absPath); //FIXME: what about pending move operations?

        return $this->getNodeByPath($path, 'Version\\Version');
    }
    /**
     * Removes the cache of the predecessor version after the node has been
     * checked in.
     *
     * TODO: document more clearly. This looks like copy-paste from checkin
     *
     * @see VersionManager::checkout
     */
    public function checkout($absPath)
    {
        $this->transport->checkoutItem($absPath); //FIXME: what about pending move operations?
    }

    /**
     * Restore the node at $nodePath to the version at $versionPath
     *
     * Clears the node's cache after it has been restored.
     *
     * TODO: This is incomplete. Needs batch processing to implement restoring an array of versions
     *
     * @param bool $removeExisting whether to remove the existing current
     *      version or create a new version after that version
     * @param string $versionPath
     * @param string $nodePath    absolute path to the node
     */
    public function restore($removeExisting, $versionPath, $nodePath)
    {
        // TODO: handle pending move operations?

        if (isset($this->objectsByPath['Node'][$nodePath])) {
            $this->objectsByPath['Node'][$nodePath]->setDirty();
        }
        if (isset($this->objectsByPath['Version\\Version'][$versionPath])) {
            $this->objectsByPath['Version\\Version'][$versionPath]->setDirty();
        }

        $this->transport->restoreItem($removeExisting, $versionPath, $nodePath);
    }

    /**
     * Remove a version given the path to the version node and the version name.
     *
     * @param string $versionPath The path to the version node
     * @param string $versionName The name of the version to remove
     *
     * @throws \PHPCR\UnsupportedRepositoryOperationException
     * @throws \PHPCR\ReferentialIntegrityException
     * @throws \PHPCR\Version\VersionException
     */
    public function removeVersion($versionPath, $versionName)
    {
        $this->transport->removeVersion($versionPath, $versionName);

        // Adjust the in memory state
        $absPath = $versionPath . '/' . $versionName;
        if (isset($this->objectsByPath['Node'][$absPath])) {
            /** @var $node Node */
            $node = $this->objectsByPath['Node'][$absPath];
            unset($this->objectsByUuid[$node->getIdentifier()]);
            $node->setDeleted();
        }

        if (isset($this->objectsByPath['Version\\Version'][$absPath])) {
            /** @var $version \Jackalope\Version\Version */
            $version = $this->objectsByPath['Version\\Version'][$absPath];
            unset($this->objectsByUuid[$version->getIdentifier()]);
            $version->setDeleted();
        }

        unset($this->objectsByPath['Node'][$absPath]);
        unset($this->objectsByPath['Version\\Version'][$absPath]);

        $this->cascadeDelete($absPath, false);
        $this->cascadeDeleteVersion($absPath);
    }

    /**
     * Refresh cached items from the backend.
     *
     * @param boolean $keepChanges whether to keep local changes or discard
     *      them.
     *
     * @see Session::refresh()
     */
    public function refresh($keepChanges)
    {
        if (! $keepChanges) {
            // revert all scheduled add, remove and move operations

            $this->operationsLog = array();

            foreach ($this->nodesAdd as $path => $operation) {
                if (! $operation->skip) {
                    $operation->node->setDeleted();
                    unset($this->objectsByPath['Node'][$path]); // did you see anything? it never existed
                }
            }
            $this->nodesAdd = array();

            // the code below will set this to dirty again. but it must not
            // be in state deleted or we will fail the sanity checks
            foreach ($this->propertiesRemove as $path => $operation) {
                $operation->property->setClean();
            }
            $this->propertiesRemove = array();
            foreach ($this->nodesRemove as $path => $operation) {
                $operation->node->setClean();

                $this->objectsByPath['Node'][$path] = $operation->node; // back in glory

                $parentPath = PathHelper::getParentPath($path);
                if (array_key_exists($parentPath, $this->objectsByPath['Node'])) {
                    // tell the parent about its restored child
                    $this->objectsByPath['Node'][$parentPath]->addChildNode($operation->node, false);
                }
            }
            $this->nodesRemove = array();

            foreach (array_reverse($this->nodesMove) as $operation) {
                if (isset($this->objectsByPath['Node'][$operation->dstPath])) {
                    // not set if we moved twice
                    $item = $this->objectsByPath['Node'][$operation->dstPath];
                    $item->setPath($operation->srcPath);
                }
                $parentPath = PathHelper::getParentPath($operation->dstPath);
                if (array_key_exists($parentPath, $this->objectsByPath['Node'])) {
                    // tell the parent about its restored child
                    $this->objectsByPath['Node'][$parentPath]->unsetChildNode(PathHelper::getNodeName($operation->dstPath), false);
                }
                // TODO: from in a two step move might fail. we should merge consecutive moves
                $parentPath = PathHelper::getParentPath($operation->srcPath);
                if (array_key_exists($parentPath, $this->objectsByPath['Node']) && isset($item) && $item instanceof Node) {
                    // tell the parent about its restored child
                    $this->objectsByPath['Node'][$parentPath]->addChildNode($item, false);
                }
                // move item to old location
                $this->objectsByPath['Node'][$operation->srcPath] = $this->objectsByPath['Node'][$operation->dstPath];
                unset($this->objectsByPath['Node'][$operation->dstPath]);
            }
            $this->nodesMove = array();
        }

        $this->objectsByUuid = array();

        /** @var $node Node */
        foreach ($this->objectsByPath['Node'] as $node) {
            if (! $keepChanges || ! ($node->isDeleted() || $node->isNew())) {
                // if we keep changes, do not restore a deleted item
                $this->objectsByUuid[$node->getIdentifier()] = $node->getPath();
                $node->setDirty($keepChanges);
            }
        }
    }

    /**
     * Determine if any object is modified and not saved to storage.
     *
     * @return boolean true if this session has any pending changes.
     *
     * @see Session::hasPendingChanges()
     */
    public function hasPendingChanges()
    {
        if (count($this->operationsLog)) {
            return true;
        }
        foreach ($this->objectsByPath['Node'] as $item) {
            if ($item->isModified()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove the item at absPath from local cache and keep information for undo.
     *
     * @param string $absPath The absolute path of the item that is being
     *      removed. Note that contrary to removeItem(), this path is the full
     *      path for a property too.
     * @param PropertyInterface $property         The item that is being removed
     * @param bool              $sessionOperation whether the property removal should be
     *      dispatched immediately or needs to be scheduled in the operations log
     *
     * @see ObjectManager::removeItem()
     */
    protected function performPropertyRemove($absPath, PropertyInterface $property, $sessionOperation = true)
    {
        if ($sessionOperation) {
            if ($property->isNew()) {
                return;
            }
            // keep reference to object in case of refresh
            $operation = new RemovePropertyOperation($absPath, $property);
            $this->propertiesRemove[$absPath] = $operation;
            $this->operationsLog[] = $operation;

            return;
        }

        // this is no session operation
        $this->transport->deletePropertyImmediately($absPath);
    }

    /**
     * Remove the item at absPath from local cache and keep information for undo.
     *
     * @param string $absPath The absolute path of the item that is being
     *      removed. Note that contrary to removeItem(), this path is the full
     *      path for a property too.
     * @param NodeInterface $node             The item that is being removed
     * @param bool          $sessionOperation whether the node removal should be
     *      dispatched immediately or needs to be scheduled in the operations log
     *
     * @see ObjectManager::removeItem()
     */
    protected function performNodeRemove($absPath, NodeInterface $node, $sessionOperation = true, $cascading = false)
    {
        if (! $sessionOperation && ! $cascading) {
            $this->transport->deleteNodeImmediately($absPath);
        }

        unset($this->objectsByUuid[$node->getIdentifier()]);
        unset($this->objectsByPath['Node'][$absPath]);

        if ($sessionOperation) {
            // keep reference to object in case of refresh
            $operation = new RemoveNodeOperation($absPath, $node);
            $this->nodesRemove[$absPath] = $operation;
            if (! $cascading) {
                $this->operationsLog[] = $operation;
            }
        }
    }


    /**
     * Notify all cached children that they are deleted as well and clean up
     * internal state
     *
     * @param string $absPath          parent node that was removed
     * @param bool   $sessionOperation to carry over the session operation information
     */
    protected function cascadeDelete($absPath, $sessionOperation = true)
    {
        foreach ($this->objectsByPath['Node'] as $path => $node) {
            if (strpos($path, "$absPath/") === 0) {
                // notify item and let it call removeItem again. save()
                // makes sure no children of already deleted items are
                // deleted again.
                $this->performNodeRemove($path, $node, $sessionOperation, true);
                if (!$node->isDeleted()) {
                    $node->setDeleted();
                }
            }
        }
    }

    /**
     * Notify all cached version children that they are deleted as well and clean up
     * internal state
     *
     * @param string $absPath parent version node that was removed
     */
    protected function cascadeDeleteVersion($absPath)
    {
        // delete all versions, similar to cascadeDelete
        foreach ($this->objectsByPath['Version\\Version'] as $path => $node) {
            if (strpos($path, "$absPath/") === 0) {
                // versions are read only, we simple unset them
                unset($this->objectsByUuid[$node->getIdentifier()]);
                unset($this->objectsByPath['Version\\Version'][$absPath]);
                if (!$node->isDeleted()) {
                    $node->setDeleted();
                }
            }
        }
    }

    /**
     * Remove a node or a property.
     *
     * If this is a node, sets all cached items below this node to deleted as
     * well.
     *
     * If property is set, the path denotes the node containing the property,
     * otherwise the node at path is removed.
     *
     * @param string $absPath The absolute path to the node to be removed,
     *      including the node name.
     * @param PropertyInterface $property optional, property instance to delete from the
     *      given node path. If set, absPath is the path to the node containing
     *      this property.
     *
     * @throws RepositoryException If node cannot be found at given path
     *
     * @see Item::remove()
     */
    public function removeItem($absPath, PropertyInterface $property = null)
    {
        if (! $this->transport instanceof WritingInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support writing');
        }

        // the object is always cached as invocation flow goes through Item::remove() without exception
        if (!isset($this->objectsByPath['Node'][$absPath])) {
            throw new RepositoryException("Internal error: Item not found in local cache at $absPath");
        }

        if ($property) {
            $absPath = PathHelper::absolutizePath($property->getName(), $absPath);
            $this->performPropertyRemove($absPath, $property);
        } else {
            $node = $this->objectsByPath['Node'][$absPath];
            $this->performNodeRemove($absPath, $node);
            $this->cascadeDelete($absPath);
        }
    }

    /**
     * Rewrites the path of a node for the movement operation, also updating
     * all cached children.
     *
     * This applies both to the cache and to the items themselves so
     * they return the correct value on getPath calls.
     *
     * @param string  $curPath Absolute path of the node to rewrite
     * @param string  $newPath The new absolute path
     */
    protected function rewriteItemPaths($curPath, $newPath)
    {
        // update internal references in parent
        $parentCurPath = PathHelper::getParentPath($curPath);
        $parentNewPath = PathHelper::getParentPath($newPath);

        if (isset($this->objectsByPath['Node'][$parentCurPath])) {
            /** @var $node Node */
            $node = $this->objectsByPath['Node'][$parentCurPath];
            if (! $node->hasNode(PathHelper::getNodeName($curPath))) {
                throw new PathNotFoundException("Source path can not be found: $curPath");
            }
            $node->unsetChildNode(PathHelper::getNodeName($curPath), true);
        }
        if (isset($this->objectsByPath['Node'][$parentNewPath])) {
            /** @var $node Node */
            $node = $this->objectsByPath['Node'][$parentNewPath];
            $node->addChildNode($this->getNodeByPath($curPath), true, PathHelper::getNodeName($newPath));
        }

        // propagate to current and children items of $curPath, updating internal path
        /** @var $node Node */
        foreach ($this->objectsByPath['Node'] as $path => $node) {
            // is it current or child?
            if ((strpos($path, $curPath . '/') === 0)||($path == $curPath)) {
                // curPath = /foo
                // newPath = /mo
                // path    = /foo/bar
                // newItemPath= /mo/bar
                $newItemPath = substr_replace($path, $newPath, 0, strlen($curPath));
                if (isset($this->objectsByPath['Node'][$path])) {
                    $node = $this->objectsByPath['Node'][$path];
                    $this->objectsByPath['Node'][$newItemPath] = $node;
                    unset($this->objectsByPath['Node'][$path]);
                    $node->setPath($newItemPath, true);
                }

                // update uuid cache
                $this->objectsByUuid[$node->getIdentifier()] = $node->getPath();
            }
        }
    }

    /**
     * WRITE: move node from source path to destination path
     *
     * @param string $srcAbsPath  Absolute path to the source node.
     * @param string $destAbsPath Absolute path to the destination where the node shall be moved to.
     *
     * @throws RepositoryException If node cannot be found at given path
     *
     * @see Session::move()
     */
    public function moveNode($srcAbsPath, $destAbsPath)
    {
        if (! $this->transport instanceof WritingInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support writing');
        }

        $srcAbsPath = PathHelper::normalizePath($srcAbsPath);
        $destAbsPath = PathHelper::normalizePath($destAbsPath, true);

        $this->rewriteItemPaths($srcAbsPath, $destAbsPath, true);
        // record every single move in case we have intermediary operations
        $operation = new MoveNodeOperation($srcAbsPath, $destAbsPath);
        $this->operationsLog[] = $operation;

        // update local cache state information
        if ($original = $this->getMoveSrcPath($srcAbsPath)) {
            $srcAbsPath = $original;
        }
        $this->nodesMove[$srcAbsPath] = $operation;
    }

    /**
     * Implement the workspace move method. It is dispatched to transport
     * immediately.
     *
     * @param string $srcAbsPath  the path of the node to be moved.
     * @param string $destAbsPath the location to which the node at srcAbsPath
     *      is to be moved.
     *
     * @throws RepositoryException If node cannot be found at given path
     *
     * @see Workspace::move()
     */
    public function moveNodeImmediately($srcAbsPath, $destAbsPath)
    {
        if (! $this->transport instanceof WritingInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support writing');
        }

        $srcAbsPath = PathHelper::normalizePath($srcAbsPath);
        $destAbsPath = PathHelper::normalizePath($destAbsPath, true);

        $this->transport->moveNodeImmediately($srcAbsPath, $destAbsPath, true); // should throw the right exceptions
        $this->rewriteItemPaths($srcAbsPath, $destAbsPath); // update local cache
    }

    /**
     * Implement the workspace removeItem method.
     *
     * @param string $absPath the absolute path of the item to be removed
     *
     * @see Workspace::removeItem
     */
    public function removeItemImmediately($absPath)
    {
        if (! $this->transport instanceof WritingInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support writing');
        }

        $absPath = PathHelper::normalizePath($absPath);
        $item = $this->session->getItem($absPath);

        // update local state and cached objects about disappeared nodes
        if ($item instanceof NodeInterface) {
            $this->performNodeRemove($absPath, $item, false);
            $this->cascadeDelete($absPath, false);
        } else {
            $this->performPropertyRemove($absPath, $item, false);
        }
        $item->setDeleted();
    }

    /**
     * Implement the workspace copy method. It is dispatched immediately.
     *
     * @param string $srcAbsPath  the path of the node to be copied.
     * @param string $destAbsPath the location to which the node at srcAbsPath
     *      is to be copied in this workspace.
     * @param string $srcWorkspace the name of the workspace from which the
     *      copy is to be made.
     *
     * @see Workspace::copy()
     */
    public function copyNodeImmediately($srcAbsPath, $destAbsPath, $srcWorkspace)
    {
        if (! $this->transport instanceof WritingInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support writing');
        }

        $srcAbsPath = PathHelper::normalizePath($srcAbsPath);
        $destAbsPath = PathHelper::normalizePath($destAbsPath, true);

        if ($this->session->nodeExists($destAbsPath)) {
            throw new ItemExistsException('Node already exists at destination (update-on-copy is currently not supported)');
            // to support this, we would have to update the local cache of nodes as well
        }
        $this->transport->copyNode($srcAbsPath, $destAbsPath, $srcWorkspace);
    }

    /**
     * Implement the workspace clone method. It is dispatched immediately.
     *      http://www.day.com/specs/jcr/2.0/3_Repository_Model.html#3.10%20Corresponding%20Nodes
     *      http://www.day.com/specs/jcr/2.0/10_Writing.html#10.8%20Cloning%20and%20Updating%20Nodes
     *
     * @param string  $srcWorkspace   the name of the workspace from which the copy is to be made.
     * @param string  $srcAbsPath     the path of the node to be cloned.
     * @param string  $destAbsPath    the location to which the node at srcAbsPath is to be cloned in this workspace.
     * @param boolean $removeExisting
     *
     * @throws \PHPCR\UnsupportedRepositoryOperationException
     * @throws \PHPCR\ItemExistsException
     *
     * @see Workspace::cloneFrom()
     */
    public function cloneFromImmediately($srcWorkspace, $srcAbsPath, $destAbsPath, $removeExisting)
    {
        if (! $this->transport instanceof WritingInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support writing');
        }

        $srcAbsPath = PathHelper::normalizePath($srcAbsPath);
        $destAbsPath = PathHelper::normalizePath($destAbsPath, true);

        if (! $removeExisting && $this->session->nodeExists($destAbsPath)) {
            throw new ItemExistsException('Node already exists at destination and removeExisting is false');
        }
        $this->transport->cloneFrom($srcWorkspace, $srcAbsPath, $destAbsPath, $removeExisting);
    }

    /**
     * WRITE: add a node at the specified path. Schedules an add operation
     * for the next save() and caches the node.
     *
     * @param string        $absPath the path to the node or property, including the item name
     * @param NodeInterface $node    The item instance that is added.
     *
     * @throws ItemExistsException if a node already exists at that path
     */
    public function addNode($absPath, NodeInterface $node)
    {
        if (! $this->transport instanceof WritingInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support writing');
        }

        if (isset($this->objectsByPath['Node'][$absPath])) {
            throw new ItemExistsException($absPath); //FIXME: same-name-siblings...
        }

        $this->objectsByPath['Node'][$absPath] = $node;
        // a new item never has a uuid, no need to add to objectsByUuid

        $operation = new AddNodeOperation($absPath, $node);
        $this->nodesAdd[$absPath] = $operation;
        $this->operationsLog[] = $operation;
    }

    /**
     * Return the permissions of the current session on the node given by path.
     * Permission can be of 4 types:
     *
     * - add_node
     * - read
     * - remove
     * - set_property
     *
     * This function will return an array containing zero, one or more of the
     * above strings.
     *
     * @param string $absPath absolute path to node to get permissions for it
     *
     * @return array of string
     */
    public function getPermissions($absPath)
    {
        if (! $this->transport instanceof PermissionInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support permissions');
        }

        return $this->transport->getPermissions($absPath);
    }

    /**
     * Clears the state of the current session
     *
     * Removes all cached objects, planned changes etc. Mostly useful for
     * testing purposes.
     *
     * @deprecated: this will screw up major, as the user of the api can still have references to nodes. USE refresh instead!
     */
    public function clear()
    {
        $this->objectsByPath = array('Node' => array());
        $this->objectsByUuid = array();
        $this->nodesAdd = array();
        $this->nodesRemove = array();
        $this->propertiesRemove = array();
        $this->nodesMove = array();
    }

    /**
     * Implementation specific: Transport is used elsewhere, provide it here
     * for Session
     *
     * @return TransportInterface
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * Begin new transaction associated with current session.
     *
     * @throws RepositoryException if the transaction implementation
     *      encounters an unexpected error condition.
     */
    public function beginTransaction()
    {
        $this->notifyItems('beginTransaction');
        $this->transport->beginTransaction();
    }

    /**
     * Complete the transaction associated with the current session.
     *
     * TODO: Make sure RollbackException and AccessDeniedException are thrown
     * by the transport if corresponding problems occur.
     *
     * @throws \PHPCR\Transaction\RollbackException if the transaction failed
     *      and was rolled back rather than committed.
     * @throws AccessDeniedException if the session is not allowed to
     *      commit the transaction.
     * @throws RepositoryException if the transaction implementation
     *      encounters an unexpected error condition.
     */
    public function commitTransaction()
    {
        $this->notifyItems('commitTransaction');
        $this->transport->commitTransaction();
    }

    /**
     * Roll back the transaction associated with the current session.
     *
     * TODO: Make sure AccessDeniedException is thrown by the transport
     * if corresponding problems occur
     * TODO: restore the in-memory state as it would be if save() was never
     * called during the transaction. The save() method will need to track some
     * undo information for this to be possible.
     *
     * @throws AccessDeniedException if the session is not allowed to
     *      roll back the transaction.
     * @throws RepositoryException if the transaction implementation
     *      encounters an unexpected error condition.
     */
    public function rollbackTransaction()
    {
        $this->transport->rollbackTransaction();
        $this->notifyItems('rollbackTransaction');
    }

    /**
     * Notifies the given node and all of its children and properties that a
     * transaction has begun, was committed or rolled back so that the item has
     * a chance to save or restore his internal state.
     *
     * @param string $method The method to call on each item for the
     *      notification (must be beginTransaction, commitTransaction or
     *      rollbackTransaction)
     *
     * @throws InvalidArgumentException if the passed $method is not valid
     */
    protected function notifyItems($method)
    {
        if (! in_array($method, array('beginTransaction', 'commitTransaction', 'rollbackTransaction'))) {
            throw new InvalidArgumentException("Unknown notification method '$method'");
        }

        // Notify the loaded nodes
        foreach ($this->objectsByPath['Node'] as $node) {
            $node->$method();
        }

        // Notify the deleted nodes
        foreach ($this->nodesRemove as $op) {
            $op->node->$method();
        }

        // Notify the deleted properties
        foreach ($this->propertiesRemove as $op) {
            $op->property->$method();
        }
    }

    /**
     * Check whether a node path has an unpersisted move operation.
     *
     * This is a simplistic check to be used by the Node to determine if it
     * should not show one of the children the backend told it would exist.
     *
     * @param string $absPath The absolute path of the node
     *
     * @return boolean true if the node has an unsaved move operation, false
     *      otherwise
     *
     * @see Node::__construct
     */
    public function isNodeMoved($absPath)
    {
        return array_key_exists($absPath, $this->nodesMove);
    }

    /**
     * Get the src path of a move operation knowing the target path.
     *
     * @param string $dstPath
     *
     * @return string|bool the source path if found, false otherwise
     */
    private function getMoveSrcPath($dstPath)
    {
        foreach ($this->nodesMove as $operation) {
            if ($operation->dstPath == $dstPath) {
                return $operation->srcPath;
            }
        }

        return false;
    }

    /**
     * Check whether the node at path has an unpersisted delete operation and
     * there is no other node moved or added there.
     *
     * This is a simplistic check to be used by the Node to determine if it
     * should not show one of the children the backend told it would exist.
     *
     * @param string $absPath The absolute path of the node
     *
     * @return boolean true if the current changed state has no node at this place
     *
     * @see Node::__construct
     */
    public function isNodeDeleted($absPath)
    {
        return array_key_exists($absPath, $this->nodesRemove)
            && !(array_key_exists($absPath, $this->nodesAdd) && !$this->nodesAdd[$absPath]->skip
                || $this->getMoveSrcPath($absPath));
    }

    /**
     * Get a node if it is already in cache or null otherwise.
     *
     * Note that this method will also return deleted node objects so you can
     * use them in refresh operations.
     *
     * @param string $absPath the absolute path to the node to fetch from cache
     *
     * @return NodeInterface or null
     *
     * @see Node::refresh()
     */
    public function getCachedNode($absPath, $class = 'Node')
    {
        if (isset($this->objectsByPath[$class][$absPath])) {
            return $this->objectsByPath[$class][$absPath];
        }
        if (array_key_exists($absPath, $this->nodesRemove)) {
            return $this->nodesRemove[$absPath]->node;
        }

        return null;
    }

    /**
     * Return an ArrayIterator containing all the cached children of the given node.
     * It makes no difference whether or not the node itself is cached.
     *
     * Note that this method will also return deleted node objects so you can
     * use them in refresh operations.
     *
     * @param string $absPath
     * @param string $class
     *
     * @return ArrayIterator
     */
    public function getCachedDescendants($absPath, $class = 'Node')
    {
        $descendants = array();

        foreach ($this->objectsByPath[$class] as $path => $node) {
            if (0 === strpos($path, "$absPath/")) {
                $descendants[$path] = $node;
            }
        }

        return new ArrayIterator(array_values($descendants));
    }

    /**
     * Get a node if it is already in cache or null otherwise.
     *
     * As getCachedNode but looking up the node by uuid.
     *
     * Note that this will never return you a removed node because the uuid is
     * removed from the map.
     *
     * @see getCachedNode
     *
     * @param $uuid
     * @param string $class
     *
     * @return NodeInterface or null
     */
    public function getCachedNodeByUuid($uuid, $class = 'Node')
    {
        if (array_key_exists($uuid, $this->objectsByUuid)) {
            return $this->getCachedNode($this->objectsByUuid[$uuid], $class);
        }

        return null;
    }

    /**
     * Purge an item given by path from the cache and return whether the node
     * should forget it or keep it.
     *
     * This is used by Node::refresh() to let the object manager notify
     * deleted nodes or detect cases when not to delete.
     *
     * @param string  $absPath     The absolute path of the item
     * @param boolean $keepChanges Whether to keep local changes or forget
     *      them
     *
     * @return bool true if the node is to be forgotten by its parent (deleted or
     *      moved away), false if child should be kept
     */
    public function purgeDisappearedNode($absPath, $keepChanges)
    {
        if (array_key_exists($absPath, $this->objectsByPath['Node'])) {
            $item = $this->objectsByPath['Node'][$absPath];

            if ($keepChanges &&
                ( $item->isNew() || $this->getMoveSrcPath($absPath))
            ) {
                // we keep changes and this is a new node or it moved here
                return false;
            }

            // may not use $item->getIdentifier here - leads to endless loop if node purges itself
            $uuid = array_search($absPath, $this->objectsByUuid);
            if (false !== $uuid) {
                unset($this->objectsByUuid[$uuid]);
            }
            unset($this->objectsByPath['Node'][$absPath]);
            $item->setDeleted();
        }
        // if the node moved away from this node, we did not find it in
        // objectsByPath and the calling parent node can forget it
        return true;
    }
}
