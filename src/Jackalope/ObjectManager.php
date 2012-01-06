<?php
namespace Jackalope;

use ArrayIterator;
use InvalidArgumentException;

use PHPCR\SessionInterface;
use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use PHPCR\ItemInterface;
use PHPCR\RepositoryException;
use PHPCR\AccessDeniedException;
use PHPCR\ItemNotFoundException;
use PHPCR\ItemExistsException;
use PHPCR\PathNotFoundException;
use PHPCR\UnsupportedRepositoryOperationException;

use PHPCR\Util\UUIDHelper;

use PHPCR\Version\VersionInterface;

use Jackalope\Transport\TransportInterface;
use Jackalope\Transport\PermissionInterface;
use Jackalope\Transport\WritingInterface;
use Jackalope\Transport\VersioningInterface;
use Jackalope\Transport\NodeTypeManagementInterface;
use Jackalope\Transport\NodeTypeCndManagementInterface;
use Jackalope\Transport\TransactionInterface;
use Jackalope\Transport\LockingInterface;

/**
 * Implementation specific class that talks to the Transport layer to get nodes
 * and caches every node retrieved to improve performance.
 *
 * For write operations, the object manager acts as the Unit of Work handler:
 * it keeps track which nodes are dirty and updates them with the transport
 * interface.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
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
     * Mapping of absolutePath => node or item object.
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

    /* properties separate? or in same array?
     * commit: make sure to delete before add, in case a node was removed and replaced with a new one
     */

    /**
     * Contains a list of items to be added to the workspace upon save.
     *
     * Keys are the full paths to be added, value is meaningless
     *
     * @var array
     */
    protected $itemsAdd = array();

    /**
     * Contains a list of items to be removed from the workspace upon save
     *
     * Keys are the full paths to be removed, value is meaningless
     *
     * @var array
     */
    protected $itemsRemove = array(); //TODO: only nodes can be in this list. call it nodesRemove?

    /**
     * Contains a list of nodes to be moved in the workspace upon save
     *
     * Keys are the source paths, values the destination paths.
     *
     * The objectsByPath array is updated immediately and any getItem and
     * similar requests are rewritten for the transport layer until save()
     *
     * Note that this list can only contain nodes, as properties can not be
     * moved.
     *
     * @var array
     */
    protected $nodesMove = array();

    /**
     * Contains a list of nodes locks
     *
     * @var array(absPath => Lock)
     */
    protected $locks = array();

    /**
     * Create the ObjectManager instance with associated session and transport
     *
     * @param FactoryInterface $factory the object factory
     * @param TransportInterface $transport
     * @param SessionInterface $session
     */
    public function __construct(FactoryInterface $factory, TransportInterface $transport, SessionInterface $session)
    {
        $this->factory = $factory;
        $this->transport = $transport;
        $this->session = $session;
    }

    /**
     * Resolves the path of an item for the current backend state (i.e. a moved
     * node is still at the source path)
     *
     * Checks the list of moved nodes whether any parents (or the node itself)
     * was moved and goes back continuing with the translated path as there can
     * be several moves of the same node.
     * Leaves the path unmodified if it was not moved.
     *
     * This method is called by ObjectManager::getFetchPath() which
     * additionally prevents requesting a deleted node or one that has been
     * moved away.
     *
     * @param string $path The current path we try to access a node from
     *
     * @return string The resolved path
     */
    protected function resolveBackendPath($path)
    {
        // any current or parent moved?
        foreach (array_reverse($this->nodesMove) as $src=>$dst) {
            if (strpos($path, $dst) === 0) {
                $path = substr_replace($path, $src, 0, strlen($dst));
            }
        }
        return $path;
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
     * @param string $class The class of node to get. TODO: Is it sane to fetch
     *      data separately for Version and normal Node?
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
    public function getNodeByPath($absPath, $class = 'Node')
    {
        $this->verifyAbsolutePath($absPath);
        $absPath = $this->normalizePath($absPath);

        if (!empty($this->objectsByPath[$class][$absPath])) {
            // Return it from memory if we already have it
            return $this->objectsByPath[$class][$absPath];
        }

        $fetchPath = $this->getFetchPath($absPath, $class); // will throw error if path is deleted

        $node = $this->factory->get(
            $class,
            array(
                $this->transport->getNode($fetchPath),
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
     * @param array $paths Array containing the absolute paths of the nodes to
     *      fetch.
     * @param string $class The class of node to get. TODO: Is it sane to
     *      fetch data separately for Version and normal Node?
     *
     * @return ArrayIterator that contains all found NodeInterface
     *      instances keyed by their path
     *
     * @throws RepositoryException If the path is not absolute or not
     *      well-formed
     *
     * @see Session::getNodes()
     */
    public function getNodesByPath($paths, $class = 'Node')
    {
        $nodes = $fetchPaths = array();

        foreach ($paths as $absPath) {
            if (!empty($this->objectsByPath[$class][$absPath])) {
                // Return it from memory if we already have it
                $nodes[$absPath] = $this->objectsByPath[$class][$absPath];
            } else {
                $fetchPaths[$absPath] = $this->getFetchPath($absPath, $class);
            }
        }

        if (!empty($fetchPaths)) {
            $data = $this->transport->getNodes($fetchPaths, $class);
            foreach ($data as $fetchPath => $item) {
                $absPath = array_search($fetchPath, $fetchPaths);
                $nodes[$absPath] = $this->factory->get(
                    $class,
                    array(
                        $item,
                        $absPath,
                        $this->session,
                        $this
                    )
                );

                if ($uuid = $nodes[$absPath]->getIdentifier()) {
                    $this->objectsByUuid[$uuid] = $absPath;
                }

                $this->objectsByPath[$class][$absPath] = $nodes[$absPath];
            }
        }

        return new ArrayIterator($nodes);
    }

    /**
     * Determine the path to be used when fetching from backend and do sanity
     * checks (locally removed nodes or parent removed or moved away)
     *
     * @param string $absPath The absolute path of the node to fetch.
     * @param string $class The class of node to get. TODO: Is it sane to fetch
     *      data separately for Version and normal Node?
     *
     * @return string fetch path
     */
    protected function getFetchPath($absPath, $class)
    {
        $absPath = $this->normalizePath($absPath);
        $this->verifyAbsolutePath($absPath);

        if (!isset($this->objectsByPath[$class])) {
            $this->objectsByPath[$class] = array();
        }

        // there is no node moved to this location, if the item is in the itemsRemove, it is really deleted
        if (array_search($absPath, $this->nodesMove) === false) {
            if (isset($this->itemsRemove[$absPath])) {
                throw new ItemNotFoundException('Path not found (node deleted in current session): ' . $absPath);
            }

            // check whether a parent node was removed
            foreach ($this->itemsRemove as $path => $dummy) {
                if (strpos($absPath, $path) === 0) {
                    throw new ItemNotFoundException('Path not found (parent node deleted in current session): ' . $absPath);
                }
            }
        }

        // was the node moved away from this location?
        if (isset($this->nodesMove[$absPath])) {
            // FIXME: this will not trigger if an ancestor was moved
            throw new ItemNotFoundException('Path not found (moved in current session): ' . $absPath);
        }

        // The path was the destination of a previous move which isn't yet dispatched to the backend.
        // I guess an exception would be fine but we can also just fetch the node from the previous path
        return $this->resolveBackendPath($absPath);
    }

    /**
     * Get the property identified by an absolute path.
     *
     * Uses the factory to instantiate a Property.
     *
     * Currently Jackalope just loads the containing node and then returns
     * the requested property of the node instance.
     *
     * @param string $absPath The absolute path of the property to create.
     * @return PropertyInterface
     *
     * @throws ItemNotFoundException if item is not found at this path
     */
    public function getPropertyByPath($absPath)
    {
        $this->verifyAbsolutePath($absPath);
        $absPath = $this->normalizePath($absPath);

        $name = substr($absPath,strrpos($absPath,'/')+1); //the property name
        $nodep = substr($absPath,0,strrpos($absPath,'/')+1); //the node this property should be in

        // OPTIMIZE: should use transport->getProperty - when we implement this, we must make sure only one instance of each property ever exists. and do the moved/deleted checks that are done in node
        $n = $this->getNodeByPath($nodep);
        try {
            return $n->getProperty($name); //throws PathNotFoundException if there is no such property
        } catch(PathNotFoundException $e) {
            throw new ItemNotFoundException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Normalizes a path according to JCR's spec (3.4.5).
     *
     * <ul>
     *   <li>All self segments(.) are removed.</li>
     *   <li>All redundant parent segments(..) are collapsed.</li>
     *   <li>If the path is an identifier-based absolute path, it is replaced by a root-based
     *       absolute path that picks out the same node in the workspace as the identifier it replaces.</li>
     * </ul>
     *
     * Note: A well-formed input path implies a well-formed and normalized path returned.
     *
     * @param string $path The path to normalize.
     * @return string The normalized path.
     */
    public function normalizePath($path)
    {
        if (strlen($path) == 0 || $path == '/') {
            return '/';
        }
        if ($path == '//') {
            return $path; // edge case that will be eaten away
        }

        if (UUIDHelper::isUUID($path)) {
            $uuid = $path;
            if (empty($this->objectsByUuid[$uuid])) {
                $finalPath = $this->transport->getNodePathForIdentifier($uuid);
                $this->objectsByUuid[$uuid] = $finalPath;
            } else {
                $finalPath = $this->objectsByUuid[$uuid];
            }
        } else {
            // TODO: when we implement Session::setNamespacePrefix to remap a
            // prefix, this should be translated here too.
            // More methods would have to call this
            $finalParts= array();
            $parts = explode('/', $path);

            foreach ($parts as $pathPart) {
                switch ($pathPart) {
                    case '.':
                        break;
                    case '..':
                        if (count($finalParts) > 1) {
                            // do not remove leading slash. "/.." is "/", not ""
                            array_pop($finalParts);
                        }
                        break;
                    default:
                        $finalParts[] = $pathPart;
                        break;
                }
            }
            if (count($finalParts) > 1 && $pathPart == '') {
                array_pop($finalParts); //avoid trailing /
            }
            $finalPath = implode('/', $finalParts);
        }

        return $finalPath;
    }

    /**
     * Makes sure $relPath is absolute, prepending $root if it is not absolute
     * already, then normalizes the path.
     *
     * If $relPath is already absolute, it is just normalized.
     *
     * If root is missing or does not start with a slash, a slash will be
     * prepended.
     * If $relPath is completely empty, the result will be $root.
     *
     * @param string $root base path to prepend to $relPath if it is not
     *      already absolute
     * @param string $relPath a relative or absolute path
     *
     * @return string Absolute and normalized path
     */
    public function absolutePath($root, $relPath)
    {
        if (strlen($relPath) && $relPath[0] != '/') {
            $root = trim($root, '/');
            if (strlen($root)) {
                $concat = "/$root/";
            } else {
                $concat = '/';
            }
            $relPath = $concat . ltrim($relPath, '/');
        } elseif (strlen($relPath)==0) {
            $relPath = $root;
        }

        return $this->normalizePath($relPath);
    }

    /**
     * Get the node identified by an uuid or (relative) path.
     *
     * If you have an absolute path use {@link getNodeByPath()} for better
     * perfromance.
     *
     * @param string $identifier uuid or (relative) path
     * @param string $root optional root if you are in a node context - not
     *      used if $identifier is an uuid
     * @param string $class optional class name for the factory
     *
     * @return NodeInterface The specified Node. if not available,
     *      ItemNotFoundException is thrown
     *
     * @throws ItemNotFoundException If the path was not found
     * @throws RepositoryException if another error occurs.
     *
     * @see Session::getNode()
     */
    public function getNode($identifier, $root = '/', $class = 'Node')
    {
        if (UUIDHelper::isUUID($identifier)) {
            if (empty($this->objectsByUuid[$identifier])) {
                $path = $this->transport->getNodePathForIdentifier($identifier);
                $node = $this->getNodeByPath($path, $class);
                $this->objectsByUuid[$identifier] = $path; //only do this once the getNodeByPath has worked
                return $node;
            }
            return $this->getNodeByPath($this->objectsByUuid[$identifier], $class);
        }
        $path = $this->absolutePath($root, $identifier);
        return $this->getNodeByPath($path, $class);
    }

    /**
     * Get the nodes identified by the given uuids or absolute paths.
     *
     * Note uuids/paths that cannot be found will be ignored
     *
     * @param string $identifiers uuid's or absolute paths
     * @param string $class optional class name for the factory
     *
     * @return ArrayIterator of NodeInterface of the specified nodes keyed by their path
     *
     * @throws RepositoryException if another error occurs.
     *
     * @see Session::getNodes()
     */
    public function getNodes($identifiers, $class = 'Node')
    {
        $paths = array();
        foreach ($identifiers as $key => $identifier) {
            if (UUIDHelper::isUUID($identifier)) {
                if (empty($this->objectsByUuid[$identifier])) {
                    try {
                        $paths[$key] = $this->transport->getNodePathForIdentifier($identifier);
                    } catch (ItemNotFoundException $e) {
                        // ignore
                    }
                } else {
                    $paths[$key] = $this->objectsByUuid[$identifier];
                }
            } else {
                $paths[$key] = $identifier;
            }
        }
        return $this->getNodesByPath($paths, $class);
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
        // TODO: should we not rather use getFetchPath ?
        return $this->transport->getBinaryStream($this->resolveBackendPath($path));  // path guaranteed to be normalized and absolute
    }

    /**
     * Returns the node types specified by name in the array or all types if no
     * filter is given.
     *
     * This is only a proxy to the transport
     *
     * @param array $nodeTypes Empty for all or specify node types by name
     *
     * @return DOMDoocument containing the nodetype information
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
     * @return DOMDocument containing the nodetype information
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
     * @param array $definitions an array of NodeTypeDefinitions
     * @param boolean $allowUpdate whether to fail if node already exists or to
     *      update it
     *
     * @return bool true on success
     */
    public function registerNodeTypes($types, $allowUpdate)
    {
        if (! $this->transport instanceof NodeTypeManagementInterface) {
            if ($this->transport instanceof NodeTypeCndManagementInterface) {
                throw new UnsupportedRepositoryOperationException('TODO: serialize the node types to cnd');
            }
            throw new UnsupportedRepositoryOperationException('Transport does not support registering node types');
        }

        return $this->transport->registerNodeTypes($types, $allowUpdate);
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
        // TODO: should we not use getFetchPath() ?
        $references = $this->transport->getReferences($this->resolveBackendPath($path), $name); // path guaranteed to be normalized and absolute
        return $this->pathArrayToPropertiesIterator($references);
    }

    /**
     * Returns all accessible WEAKREFERENCE properties in the workspace that
     * point to the node
     *
     * @param string $path the path of the referenced node
     * @param string $name name of referring WEAKREFERENCE properties to be
     *      returned; if null then all referring WEAKREFERENCEs are returned
     * @return ArrayIterator
     */
    public function getWeakReferences($path, $name = null)
    {
        // TODO: should we not use getFetchPath() ?
        $references = $this->transport->getWeakReferences($this->resolveBackendPath($path), $name); // path guaranteed to be normalized and absolute
        return $this->pathArrayToPropertiesIterator($references);
    }

    /**
     * Transform an array containing properties paths to an ArrayIterator over
     * Property objects
     *
     * @param array $array an array of properties paths
     * @return ArrayIterator
     */
    protected function pathArrayToPropertiesIterator($array)
    {
        $props = array();

        //OPTIMIZE: get all the properties in one request?
        foreach ($array as $path) {
            $prop = $this->getPropertyByPath($path); //FIXME: this will break if we have non-persisted move
            $props[] = $prop;
        }

        return new ArrayIterator($props);
    }

    /**
     * Implementation specific way to register node types from cnd with the
     * backend.
     *
     * This is only a proxy to the transport
     *
     * @param $cnd a string with cnd information
     * @param boolean $allowUpdate whether to fail if node already exists or to update it
     * @return bool true on success
     */
    public function registerNodeTypesCnd($cnd, $allowUpdate)
    {
        if (! $this->transport instanceof NodeTypeCndManagementInterface) {
            if ($this->transport instanceof NodeTypeManagementInterface) {
                throw new UnsupportedRepositoryOperationException('TODO: parse cnd and call registerNodeTypes');
            }
            throw new UnsupportedRepositoryOperationException('Transport does not support registering node types');
        }

        return $this->transport->registerNodeTypesCnd($cnd, $allowUpdate);
    }

    /**
     * Verifies the path to be absolute and well-formed.
     *
     * @param string $path the path to verify
     *
     * @return boolean always true, exception if this is not a valid path.
     *
     * @throws RepositoryException if the path is not absolute.
     */
    protected function verifyAbsolutePath($path)
    {
        if (! ($path && $path[0] == '/')) {
            throw new RepositoryException('Path is not absolute: ' . $path);
        }
        return true;
    }

    /**
     * Push all recorded changes to the backend.
     *
     * The order is important to avoid conflicts
     * 1. remove nodes
     * 2. move nodes
     * 3. add new nodes
     * 4. commit any other changes
     *
     * If transactions are enabled but we are not currently inside a
     * transaction, the session is responsible to start a transaction to make
     * sure the backend state does not get messed up in case of error.
     *
     * @return void
     */
    public function save()
    {
        if (! $this->transport instanceof WritingInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support writing');
        }

        // TODO: adjust transport to accept lists and do a diff request instead of single requests

        /* remove nodes/properties
         *
         * deleting a node deletes the whole tree
         * we have to avoid deleting children/properties of nodes we already
         * deleted. we sort the paths and then use that to check if parent path
         * was already removed in a - comparably - cheap way
         */
        $todelete = array_keys($this->itemsRemove);
        sort($todelete);
        $last = ':'; // anything but '/'
        foreach ($todelete as $path) {
            if (! strncmp($last, $path, strlen($last)) && $path[strlen($last)] == '/') {
                //parent path has already been removed
                continue;
            }
            $this->transport->deleteNode($path);
            $last = $path;
        }

        // move nodes/properties
        foreach ($this->nodesMove as $src => $dst) {
            $this->transport->moveNode($src, $dst);
            if (isset($this->objectsByPath['Node'][$dst])) {
                // might not be set if moved again afterwards
                // move is not treated as modified, need to confirm separately
                $this->objectsByPath['Node'][$dst]->confirmSaved();
            }
        }

        // filter out sub-nodes and sub-properties since the top-most nodes that are
        // added will create all sub-nodes and sub-properties at once
        $nodesToCreate = $this->itemsAdd;
        foreach ($nodesToCreate as $path => $dummy) {
            foreach ($nodesToCreate as $path2 => $dummy) {
                if (strpos($path2, $path.'/') === 0) {
                    unset($nodesToCreate[$path2]);
                }
            }
        }
        // create new items
        foreach ($nodesToCreate as $path => $dummy) {
            $item = $this->getNodeByPath($path);
            if ($item instanceof NodeInterface) {
                $this->transport->storeNode($item);
            } elseif ($item instanceof PropertyInterface) {
                $this->transport->storeProperty($item);
            } else {
                throw new RepositoryException('Internal error: Unknown type '.get_class($item));
            }
        }

        // loop through cached nodes and commit all dirty and set them to clean.
        if (isset($this->objectsByPath['Node'])) {
            foreach ($this->objectsByPath['Node'] as $path => $item) {
                if ($item->isModified()) {
                    if ($item instanceof NodeInterface) {
                        foreach ($item->getProperties() as $property) {
                            if ($property->isModified()) {
                                $this->transport->storeProperty($property);
                            }
                        }
                    } elseif ($item instanceof PropertyInterface) {
                        if ($item->getValue() === null) {
                            $this->transport->deleteProperty($path);
                        } else {
                            $this->transport->storeProperty($item);
                        }
                    } else {
                        throw new RepositoryException('Internal Error: Unknown type '.get_class($item));
                    }
                }
            }
        }

        // commit changes to the local state
        foreach ($this->itemsRemove as $path => $item) {
            unset($this->objectsByPath['Node'][$path]);
            // TODO: unset the node in $this->objectsByUuid if necessary
        }

        //clear those lists before reloading the newly added nodes from backend, to avoid collisions
        $this->itemsRemove = array();
        $this->nodesMove = array();

        foreach ($this->itemsAdd as $path => $dummy) {
            $item = $this->getNodeByPath($path);
            $item->confirmSaved();
        }
        if (isset($this->objectsByPath['Node'])) {
            foreach ($this->objectsByPath['Node'] as $item) {
                if ($item->isModified()) {
                    $item->confirmSaved();
                }
            }
        }

        $this->itemsAdd = array();
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
        if (! $this->transport instanceof VersioningInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support versioning');
        }
        $path = $this->transport->checkinItem($absPath); //FIXME: what about pending move operations?
        $node = $this->getNodeByPath($path, 'Version\\Version');
        $predecessorUuids = $node->getProperty('jcr:predecessors')->getString();
        if (!empty($predecessorUuids[0]) && isset($this->objectsByUuid[$predecessorUuids[0]])) {
            $dirtyPath = $this->objectsByUuid[$predecessorUuids[0]];
            unset($this->objectsByPath['Version\\Version'][$dirtyPath]);
            unset($this->objectsByPath['Node'][$dirtyPath]); //FIXME: the node object should be told about this
            unset($this->objectsByUuid[$predecessorUuids[0]]);
        }
        return $node;
    }
    /**
     * Removes the cache of the predecessor version after the node has been
     * checked in.
     *
     * TODO: document more clearly. This looks like copy-paste from checkin
     *
     * @see VersionManager::checkout
     *
     * @return void
     */
    public function checkout($absPath)
    {
        if (! $this->transport instanceof VersioningInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support versioning');
        }
        $this->transport->checkoutItem($absPath); //FIXME: what about pending move operations?
    }

    /**
     * Removes the node's cache after it has been restored.
     *
     * TODO: This is incomplete. Needs batch processing to avoid
     * chicken-and-egg problems.
     */
    public function restore($removeExisting, $vpath, $absPath)
    {
        if (! $this->transport instanceof VersioningInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support versioning');
        }

        if (null !== $absPath
            && (isset($this->objectsByPath['Node'][$absPath]) || isset($this->objectsByPath['Version\\Version'][$absPath]))
        ) {
            unset($this->objectsByUuid[$this->objectsByPath['Node'][$absPath]->getIdentifier()]);
            unset($this->objectsByPath['Version\Version'][$absPath]);
            unset($this->objectsByPath['Node'][$absPath]);
        }
        $this->transport->restoreItem($removeExisting, $vpath, $absPath);  //FIXME: what about pending move operations?
    }

    /**
     * Get the uuid of the version history node at $path
     *
     * @param string $path the path to the node we want the version
     *
     * @return string uuid of the version history node
     */
    public function getVersionHistory($path)
    {
        if (! $this->transport instanceof VersioningInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support versioning');
        }

        return $this->transport->getVersionHistory($this->resolveBackendPath($path));
    }

    /**
     * Remove a version given the path to the version node and the version name.
     *
     * @param $versionPath The path to the version node
     * @param $versionName The name of the version to remove
     * @return void
     *
     * @throws \PHPCR\UnsupportedRepositoryOperationException
     * @throws PHPCR\ReferentialIntegrityException
     * @throws PHPCR\Version\VersionException
     */
    public function removeVersion($versionPath, $versionName)
    {
        if (! $this->transport instanceof VersioningInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support versioning');
        }

        $this->transport->removeVersion($versionPath, $versionName);

        // Adjust the in memory state
        $path = $versionPath . '/' . $versionName;

        if (isset($this->objectsByPath['Node'][$path])) {
            $node = $this->objectsByPath['Node'][$path];
            $node->setDeleted();
        }

        if (isset($this->objectsByPath['Version\\Version'][$path])) {
            $version = $this->objectsByPath['Version\\Version'][$path];
            $version->setDeleted();
        }

        unset($this->objectsByPath['Node'][$path]);
        unset($this->objectsByPath['Version\\Version'][$path]);
    }

    /**
     * Refresh cached items from the backend.
     *
     * @param boolean $keepChanges whether to keep local changes or discard
     *      them.
     *
     * @return void
     *
     * @see Session::refresh()
     */
    public function refresh($keepChanges)
    {
        if (! $keepChanges) {
            // revert all scheduled add, remove and move operations

            foreach ($this->itemsAdd as $path => $dummy) {
                $this->objectsByPath['Node'][$path]->setDeleted();
                unset($this->objectsByPath['Node'][$path]); // did you see anything? it never existed
            }
            $this->itemsAdd = array();

            foreach ($this->itemsRemove as $path => $item) {
                // the code below will set this to dirty again. but it must not
                // be in state deleted or we will fail the sanity checks
                $item->setClean();

                if ($item instanceof Node) {
                    $this->objectsByPath['Node'][$path] = $item; // back in glory
                }
                $parentPath = strtr(dirname($path), '\\', '/');
                if (array_key_exists($parentPath, $this->objectsByPath['Node'])) {
                    // tell the parent about its restored child
                    $this->objectsByPath['Node'][$parentPath]->addChildNode($item->getName(), false);
                }
            }
            $this->itemsRemove = array();

            foreach (array_reverse($this->nodesMove) as $from => $to) {
                if (isset($this->objectsByPath['Node'][$to])) {
                    // not set if we moved twice
                    $item = $this->objectsByPath['Node'][$to];
                    $item->setPath($from);
                }
                $parentPath = strtr(dirname($to), '\\', '/');
                if (array_key_exists($parentPath, $this->objectsByPath['Node'])) {
                    // tell the parent about its restored child
                    $this->objectsByPath['Node'][$parentPath]->unsetChildNode(basename($to), false);
                }
                // TODO: from in a two step move might fail. we should merge consecutive moves
                $parentPath = strtr(dirname($from), '\\', '/');
                if (array_key_exists($parentPath, $this->objectsByPath['Node'])) {
                    // tell the parent about its restored child
                    $this->objectsByPath['Node'][$parentPath]->addChildNode(basename($from), false);
                }
                // move item to old location
                $this->objectsByPath['Node'][$from] = $this->objectsByPath['Node'][$to];
                unset($this->objectsByPath['Node'][$to]);
            }
            $this->nodesMove = array();
        }

        foreach ($this->objectsByPath['Node'] as $path => $item) {
            if (! $keepChanges || ! ($item->isDeleted() || $item->isNew())) {
                // if we keep changes, do not restore a deleted item
                $item->setDirty($keepChanges);
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
        if (count($this->itemsAdd) || count($this->nodesMove) || count($this->itemsRemove)) {
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
     * @param ItemInterface $item The item that is being removed
     *
     * @return void
     *
     * @see ObjectManager::removeItem()
     */
    protected function performRemove($absPath, ItemInterface $item)
    {
        // was any parent moved?
        foreach ($this->nodesMove as $dst) {
            if (strpos($dst, $absPath) === 0) {
                // this is MOVE, then DELETE but we dispatch DELETE before MOVE
                // TODO we might could just remove the MOVE and put a DELETE on the previous node :)
                throw new RepositoryException('Internal error: Deleting ('.$absPath.') will fail because your move is dispatched to the server after the delete');
            }
        }

        if ($item instanceof Node) {
            unset($this->objectsByUuid[$item->getIdentifier()]);
        }
        unset($this->objectsByPath['Node'][$absPath]);

        if (isset($this->itemsAdd[$absPath])) {
            //this is a new unsaved node
            unset($this->itemsAdd[$absPath]);
        } else {
            // keep reference to object in case of refresh
            // the topmost item delete will be sent to backend and cascade delete
            $this->itemsRemove[$absPath] = $item;
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
     * @param string $property optional, property instance to delete from the
     *      given node path. If set, absPath is the path to the node containing
     *      this property.
     *
     * @return void
     *
     * @throws RepositoryException If node cannot be found at given path
     *
     * @see Item::remove()
     */
    public function removeItem($absPath, $property = null)
    {
        if (! $this->transport instanceof WritingInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support writing');
        }

        // the object is always cached as invocation flow goes through Item::remove() without exception
        if (!isset($this->objectsByPath['Node'][$absPath])) {
            throw new RepositoryException("Internal error: Item not found in local cache at $absPath");
        }

        if ($property) {
            $item = $property;
            $absPath = $this->absolutePath($absPath, $property->getName());
        } else {
            $item = $this->objectsByPath['Node'][$absPath];
        }
        $this->performRemove($absPath, $item);

        if ($property) {
            // property has no children
            return;
        }

        // notify all cached children that they are deleted as well and clean up internal state
        $todelete = array();
        foreach ($this->objectsByPath['Node'] as $path => $item) {
            if (strpos($path, "$absPath/") === 0) {
                // notify item and let it call removeItem again. save()
                // makes sure no children of already deleted items are
                // deleted again.
                $this->performRemove($path, $item);
                $todelete[] = $item;
            }
        }
        // delay notification to still be able to access the uuid property in performRemove
        foreach ($todelete as $item) {
            if (! $item->isDeleted()) {
                $item->setDeleted();
            }
        }
    }

    /**
     * Rewrites the path of a node for the movement operation, also updating
     * all cached children.
     *
     * This applies both to the cache and to the items themselves so
     * they return the correct value on getPath calls.
     *
     * Does some magic detection if for example you ADD a node and then rewrite
     * (MOVE) that exact node: skips the MOVE and just ADDs to the new place.
     * The return value denotes whether a MOVE must still be dispatched to the
     * backend.
     *
     * @param string $curPath Absolute path of the node to rewrite
     * @param string $newPath The new absolute path
     * @param boolean $session Whether this is a session or an immediate move
     *
     * @return boolean Whether dispatching the move to the backend is still
     *      required (otherwise the move has been replaced with another
     *      operation - see the add + move example above)
     */
    protected function rewriteItemPaths($curPath, $newPath, $session = false)
    {
        $moveRequired = true;

        // update internal references in parent
        $parentCurPath = dirname($curPath);
        $parentNewPath = dirname($newPath);
        if (isset($this->objectsByPath['Node'][$parentCurPath])) {
            $node = $this->objectsByPath['Node'][$parentCurPath];
            if (! $node->hasNode(basename($curPath))) {
                throw new PathNotFoundException("Source path can not be found: $curPath");
            }
            $node->unsetChildNode(basename($curPath), true);
        }
        if (isset($this->objectsByPath['Node'][$parentNewPath])) {
            $node = $this->objectsByPath['Node'][$parentNewPath];
            $node->addChildNode(basename($newPath), true);
        }

        // propagate to current and children items of $curPath, updating internal path
        foreach ($this->objectsByPath['Node'] as $path=>$item) {
            // is it current or child?
            if (strpos($path, $curPath) === 0) {
                // curPath = /foo
                // newPath = /mo
                // path    = /foo/bar
                // newItemPath= /mo/bar
                $newItemPath = substr_replace($path, $newPath, 0, strlen($curPath));
                if (isset($this->itemsAdd[$path])) {
                    $this->itemsAdd[$newItemPath] = 1;
                    unset($this->itemsAdd[$path]);
                    if ($path === $curPath) {
                        $moveRequired = false;
                    }
                }
                if (isset($this->objectsByPath['Node'][$path])) {
                    $item = $this->objectsByPath['Node'][$path];
                    $this->objectsByPath['Node'][$newItemPath] = $item;
                    unset($this->objectsByPath['Node'][$path]);
                    $item->setPath($newItemPath, $session);
                }
            }
        }
        return $moveRequired;
    }

    /**
     * WRITE: move node from source path to destination path
     *
     * @param string $srcAbsPath Absolute path to the source node.
     * @param string $destAbsPath Absolute path to the destination where the node shall be moved to.
     *
     * @return void
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

        if ($this->rewriteItemPaths($srcAbsPath, $destAbsPath, true)) {
            // TODO collapse multiple consecutive move operations here
            $this->nodesMove[$srcAbsPath] = $destAbsPath;
        }
    }

    /**
     * Implement the workspace move method. It is dispatched to transport
     * immediately.
     *
     * @param string $srcAbsPath the path of the node to be moved.
     * @param string $destAbsPath the location to which the node at srcAbsPath
     *      is to be moved.
     *
     * @return void
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

        $this->verifyAbsolutePath($srcAbsPath);
        $this->verifyAbsolutePath($destAbsPath);

        $this->transport->moveNode($srcAbsPath, $destAbsPath); // should throw the right exceptions
        $this->rewriteItemPaths($srcAbsPath, $destAbsPath); // update local cache
    }

    /**
     * Implement the workspace copy method. It is dispatched immediately.
     *
     * @param string $srcAbsPath the path of the node to be copied.
     * @param string $destAbsPath the location to which the node at srcAbsPath
     *      is to be copied in this workspace.
     * @param string $srcWorkspace the name of the workspace from which the
     *      copy is to be made.
     *
     * @return void
     *
     * @see Workspace::copy()
     */
    public function copyNodeImmediately($srcAbsPath, $destAbsPath, $srcWorkspace)
    {
        if (! $this->transport instanceof WritingInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support writing');
        }

        $this->verifyAbsolutePath($srcAbsPath);
        $this->verifyAbsolutePath($destAbsPath);

        if ($this->session->nodeExists($destAbsPath)) {
            throw new ItemExistsException('Node already exists at destination (update-on-copy is currently not supported)');
            // to support this, we would have to update the local cache of nodes as well
        }
        $this->transport->copyNode($srcAbsPath, $destAbsPath, $srcWorkspace);
    }

    /**
     * WRITE: add an item at the specified path. Schedules an add operation
     * for the next save() and caches the item.
     *
     * @param string $absPath the path to the node or property, including the item name
     * @param ItemInterface $item The item instance that is added.
     *
     * @throws ItemExistsException if a node already exists at that path
     */
    public function addItem($absPath, ItemInterface $item)
    {
        if (! $this->transport instanceof WritingInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support writing');
        }

        if (isset($this->objectsByPath['Node'][$absPath])) {
            throw new ItemExistsException($absPath); //FIXME: same-name-siblings...
        }
        $this->objectsByPath['Node'][$absPath] = $item;
        // a new item never has a uuid, no need to add to objectsByUuid

        $this->itemsAdd[$absPath] = 1;
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
        $this->itemsAdd = array();
        $this->itemsRemove = array();
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
     * @return void
     *
     * @throws RepositoryException if the transaction implementation
     *      encounters an unexpected error condition.
     */
    public function beginTransaction()
    {
        if (! $this->transport instanceof TransactionInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support transactions');
        }

        $this->notifyItems('beginTransaction');
        $this->transport->beginTransaction();
    }

    /**
     * Complete the transaction associated with the current session.
     *
     * TODO: Make sure RollbackException and AccessDeniedException are thrown
     * by the transport if corresponding problems occur.
     *
     * @return void
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
        if (! $this->transport instanceof TransactionInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support transactions');
        }

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
     * @return void
     *
     * @throws AccessDeniedException if the session is not allowed to
     *      roll back the transaction.
     * @throws RepositoryException if the transaction implementation
     *      encounters an unexpected error condition.
     */
    public function rollbackTransaction()
    {
        if (! $this->transport instanceof TransactionInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support transactions');
        }

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
     * @return void
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
        foreach ($this->itemsRemove as $item) {
            $item->$method();
        }
    }

    /**
     * Check whether a node path has an unpersisted move operation
     *
     * @param string $absPath The absolute path of the node
     *
     * @return boolean true if the node has an unsaved move operation, false
     *      otherwise
     */
    public function isNodeMoved($absPath)
    {
        return array_key_exists($absPath, $this->nodesMove);
    }

    /**
     * Check whether an item path has an unpersisted delete operation and
     * there is no other node moved or added there
     *
     * @param string $absPath The absolute path of the node
     *
     * @return boolean true if the current changed state has no node at this place
     */
    public function isItemDeleted($absPath)
    {
        return array_key_exists($absPath, $this->itemsRemove) &&
             ! (array_key_exists($absPath, $this->itemsAdd) ||
                array_search($absPath, $this->nodesMove) !== false);
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
    public function getCachedNode($absPath)
    {
        if (array_key_exists($absPath, $this->objectsByPath['Node'])) {
            return $this->objectsByPath['Node'][$absPath];
        } elseif (array_key_exists($absPath, $this->itemsRemove)) {
            return $this->itemsRemove[$absPath];
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
     * @param string $absPath The absolute path of the item
     * @param boolean $keepChanges Whether to keep local changes or forget
     *      them
     *
     * @return true if the node is to be forgotten by its parent (deleted or
     *      moved away), false if child should be kept
     */
    public function purgeDisappearedNode($absPath, $keepChanges)
    {
        if (array_key_exists($absPath, $this->objectsByPath['Node'])) {
            $item = $this->objectsByPath['Node'][$absPath];

            if ($keepChanges &&
                ( $item->isNew() || false !== array_search($absPath, $this->nodesMove))
            ) {
                // we keep changes and this is a new node or it moved here
                return false;
            }

            $uuid = $item->getIdentifier();
            unset($this->objectsByPath['Node'][$absPath]);
            if (array_key_exists($uuid, $this->objectsByUuid)) {
                unset($this->objectsByUuid[$uuid]);
            }
            $item->setDeleted();
        }
        // if the node moved away from this node, we did not find it in
        // objectsByPath and the calling parent node can forget it

        return true;
    }

    public function lockNode($absPath, $isDeep, $isSessionScoped, $timeoutHint, $ownerInfo)
    {
        if (! $this->transport instanceof LockingInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support locking');
        }

        // If the node does not exist, Jackrabbit will return an HTTP 412 error which is
        // the same as if the node was not assigned the 'mix:lockable' mixin. To avoid
        // problems in determining which of those error it would be, it's easier to detect
        // non-existing nodes earlier.
        if (!isset($this->objectsByPath['Node'][$absPath])) {
            throw new \PHPCR\PathNotFoundException("Unable to lock unexisting node '$absPath'");
        }

        // TODO: this does not work, there seem to be a problem with setting the node state after
        // a session.save it is always DIRTY when it should be clean.
//        $state = $this->objectsByPath['Node'][$absPath]->getState();
//        if ($state !== \Jackalope\Item::STATE_NEW || $state !== \Jackalope\Item::STATE_CLEAN) {
//            throw new \PHPCR\InvalidItemStateException("Cannot lock the non-clean node '$absPath'");
//        }

        try
        {
            $res = $this->transport->lockNode($absPath, $isDeep, $isSessionScoped, $timeoutHint, $ownerInfo);
        }
        catch (\PHPCR\RepositoryException $ex)
        {
            // Check if it's a 412 error, otherwise re-throw the same exception
            if (preg_match('/Response \(HTTP 412\):/', $ex->getMessage()))
            {
                throw new \PHPCR\Lock\LockException("Unable to lock the non-lockable node '$absPath': " . $ex->getMessage(), 412);
            }

            // Any other exception will simply be rethrown
            throw $ex;
        }

        // Store the lock for further use
        $this->locks[$absPath] = $res;

        return $res;
    }

    public function isLocked($absPath)
    {
        if (! $this->transport instanceof LockingInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support locking');
        }

        return $this->transport->isLocked($absPath);
    }

    public function unlock($absPath)
    {
        if (! $this->transport instanceof LockingInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support locking');
        }

        if (!isset($this->objectsByPath['Node'][$absPath])) {
            throw new \PHPCR\PathNotFoundException("Unable to unlock unexisting node '$absPath'");
        }

        if (!array_key_exists($absPath, $this->locks)) {
            throw new \PHPCR\Lock\LockException("Unable to find a lock active lock for the node '$absPath'");
        }

        $this->transport->unlock($absPath, $this->locks[$absPath]->getLockToken());
    }
}
