<?php
/**
 * Class to handle nodes using a specific transport layer.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 *
 * @package jackalope
 */

namespace Jackalope;

/**
 * Implementation specific class that talks to the Transport layer to get nodes
 * and caches every node retrieved to improve performance.
 *
 * For update method, the object manager keeps track which nodes are dirty so it
 * knows what to give to transport to write to the backend.
 *
 * @package jackalope
 */
class ObjectManager
{
    /**
     * Instance of an implementation of the \PHPCR\SessionInterface.
     * @var \PHPCR\SessionInterface
     */
    protected $session;

    /**
     * Instance of an implementation of the TransportInterface
     * @var TransportInterface
     */
    protected $transport;

    /**
     * Mapping of absolutePath => node object.
     *
     * There is no notion of order here. The order is defined by order in Node::nodes array.
     *
     * @var array
     */
    protected $objectsByPath = array();

    /**
     * Mapping of uuid to an absolutePath.
     *
     * Take care never to put a path in here unless there is a node for that path in objectsByPath.
     *
     * @var array
     */
    protected $objectsByUuid = array();

    /* properties separate? or in same array?
     * commit: make sure to delete before add, in case a node was removed and replaced with a new one
     */

    /**
     * Contains a list of node to be added to the workspace.
     * @var array
     */
    protected $nodesAdd = array();

    /**
     * Contains a list of node to be removed from the workspace.
     * @var array
     */
    protected $nodesRemove = array();

    /**
     * Contains a list of node to be moved in the workspace.
     * @var array
     */
    protected $nodesMove = array();

    /**
     * identifier to determine if the current objectManager is in an unsaved state.
     * @var boolean
     */
    protected $unsaved = false;

    /**
     * Registers the provided parameters as attribute to the instance.
     *
     * @param TransportInterface $transport
     * @param \PHPCR\SessionInterface $session
     */
    public function __construct(TransportInterface $transport, \PHPCR\SessionInterface $session)
    {
        $this->transport = $transport;
        $this->session = $session;
    }

    /**
     * Get the node identified by an absolute path.
     *
     * To prevent unnecessary work to be done a register will be written containing already retrieved nodes.
     * Unfortunately there is currently no way to refetch a node once it has been fetched.
     *
     * @param string $absPath The absolute path of the node to create.
     * @return \PHPCR\Node
     *
     * @throws \PHPCR\ItemNotFoundException If nothing is found at that absolute path
     * @throws \PHPCR\RepositoryException    If the path is not absolute or not well-formed
     *
     * @uses Factory::get()
     */
    public function getNodeByPath($absPath)
    {
        $absPath = $this->normalizePath($absPath);
        $this->verifyAbsolutePath($absPath);

        if (empty($this->objectsByPath[$absPath])) {
            if (isset($this->nodesRemove[$absPath])) {
                throw new \PHPCR\PathNotFoundException('Path not found (deleted in current session): ' . $uri);
            }
            $node = Factory::get(
                'Node',
                array(
                    $this->transport->getItem($absPath),
                    $absPath,
                    $this->session,
                    $this
                )
            );
            $this->objectsByUuid[$node->getIdentifier()] = $absPath; //FIXME: what about nodes that are NOT referencable?
            $this->objectsByPath[$absPath] = $node;
        }

        return $this->objectsByPath[$absPath];
    }

    /**
     * Get the property identified by an absolute path.
     * Uses the factory to instantiate Property
     *
     * @param string $absPath The absolute path of the property to create.
     * @return \PHPCR\Property
     */
    public function getPropertyByPath($absPath)
    {
        $absPath = $this->normalizePath($absPath);

        $this->verifyAbsolutePath($absPath);

        $name = substr($absPath,strrpos($absPath,'/')+1); //the property name
        $nodep = substr($absPath,0,strrpos($absPath,'/')+1); //the node this property should be in

        /* OPTIMIZE? instead of fetching the node, we could make Transport provide it with a
         * GET /server/tests/jcr%3aroot/tests_level1_access_base/multiValueProperty/jcr%3auuid
         * (davex getItem uses json, which is not applicable to properties)
         */
        $n = $this->getNodeByPath($nodep);
        return $n->getProperty($name); //throws PathNotFoundException if there is no such property
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
        // UUDID is HEX_CHAR{8}-HEX_CHAR{4}-HEX_CHAR{4}-HEX_CHAR{4}-HEX_CHAR{12}
        if (preg_match('/^\[([[:xdigit:]]{8}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{12})\]$/', $path, $matches)) {
            $uuid = $matches[1];
            if (empty($this->objectsByUuid[$uuid])) {
                $finalPath = $this->transport->getNodePathForIdentifier($uuid);
                $this->objectsByUuid[$uuid] = $finalPath;
            } else {
                $finalPath = $this->objectsByUuid[$uuid];
            }
        } else {
            $finalParts= array();
            $abs = ($path && $path[0] == '/');
            $parts = explode('/', $path);
            foreach ($parts as $pathPart) {
                switch ($pathPart) {
                    case '.':
                    case '':
                        break;
                    case '..':
                        array_pop($finalParts);
                        break;
                    default:
                        array_push($finalParts, $pathPart);
                        break;
                }
            }
            $finalPath = implode('/', $finalParts);
            if ($abs) {
                $finalPath = '/'.$finalPath;
            }
        }
        return $finalPath;
    }

    /**
     * Creates an absolute path from a root and a relative path and then normalizes it.
     *
     * If root is missing or does not start with a slash, a slash will be prepended
     *
     * @param string Root path to append the relative
     * @param string Relative path
     * @return string Absolute and normalized path
     */
    public function absolutePath($root, $relPath)
    {
        $root = trim($root, '/');
        if (strlen($root)) {
            $concat = "/$root/";
        } else {
            $concat = '/';
        }
        $concat .= ltrim($relPath, '/');

        // TODO: maybe this should be required explicitly and not called from within this method...
        return $this->normalizePath($concat);
    }

    /**
     * Get the node idenfied by an uuid or path or root path and relative path.
     *
     * If you have an absolute path use getNodeByPath.
     *
     * @param string uuid or relative path
     * @param string optional root if you are in a node context - not used if $identifier is an uuid
     * @return \PHPCR\Node The specified Node. if not available, ItemNotFoundException is thrown
     *
     * @throws \PHPCR\ItemNotFoundException If the path was not found
     * @throws \PHPCR\RepositoryException if another error occurs.
     */
    public function getNode($identifier, $root = '/')
    {
        if ($this->isUUID($identifier)) {
            if (empty($this->objectsByUuid[$identifier])) {
                $path = $this->transport->getNodePathForIdentifier($identifier);
                $node = $this->getNodeByPath($path);
                $this->objectsByUuid[$identifier] = $path; //only do this once the getNodeByPath has worked
                return $node;
            } else {
                return $this->getNodeByPath($this->objectsByUuid[$identifier]);
            }
        } else {
            $path = $this->absolutePath($root, $identifier);
            return $this->getNodeByPath($path);
        }
    }

    /**
     * This is only a proxy to the transport it returns all node types if none is given or only the ones given as array.
     *
     * @param array $nodeTypes Empty for all or selected node types by name
     * @return DOMDoocument containing the nodetype information
     */
    public function getNodeTypes($nodeTypes = array())
    {
        return $this->transport->getNodeTypes($nodeTypes);
    }

    /**
     * Get a single nodetype.
     *
     * @param string the nodetype you want
     * @return DOMDocument containing the nodetype information
     *
     * @see getNodeTypes()
     */
    public function getNodeType($nodeType)
    {
        return $this->getNodeTypes(array($nodeType));
    }

    /**
     * Verifies the path to be absolute and well-formed.
     *
     * @param string $path the path to verify
     * @return boolean Always true :)
     *
     * @throws \PHPCR\RepositoryException if the path is not absolute or well-formed
     */
    public function verifyAbsolutePath($path)
    {
        if (!Helper::isAbsolutePath($path)) {
            throw new \PHPCR\RepositoryException('Path is not absolute: ' . $path);
        }
        if (!Helper::isValidPath($path)) {
            throw new \PHPCR\RepositoryException('Path is not well-formed (TODO: match against spec): ' . $path);
        }
        return true;
    }

    /**
     * Checks if the string could be a uuid.
     *
     * @param string $id Possible uuid
     * @return boolean True if the test was passed, else false.
     */
    protected function isUUID($id)
    {
        // UUDID is HEX_CHAR{8}-HEX_CHAR{4}-HEX_CHAR{4}-HEX_CHAR{4}-HEX_CHAR{12}
        if (1 === preg_match('/^[[:xdigit:]]{8}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{12}$/', $id)) {
            return true;
        } else {
            return false;
        }
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
     * @return void
     */
    public function save()
    {
        // TODO: start transaction

        // remove nodes/properties
        foreach($this->nodesRemove as $path => $dummy) {
            $this->transport->deleteItem($path);
        }

        // move nodes/properties
        foreach($this->nodesMove as $src => $dst) {
            //TODO: have a davex client method to move a path
        }

        // filter out sub-nodes and sub-properties since the top-most nodes that are
        // added will create all sub-nodes and sub-properties at once
        $nodesToCreate = $this->nodesAdd;
        foreach ($nodesToCreate as $path => $dummy) {
            foreach ($nodesToCreate as $path2 => $dummy) {
                if (strpos($path2, $path.'/') === 0) {
                    unset($nodesToCreate[$path2]);
                }
            }
        }
        // create new nodes
        foreach($nodesToCreate as $path => $dummy) {
            $item = $this->getNodeByPath($path);
            if ($item instanceof \PHPCR\NodeInterface) {
                $this->transport->storeItem($path, $item->getProperties(), $item->getNodes());
            } elseif ($item instanceof \PHPCR\PropertyInterface) {
                $this->transport->storeProperty($path, $item);
            } else {
                throw new \UnexpectedValueException('Unknown type '.get_class($item));
            }
        }

        //loop through cached nodes and commit all dirty and set them to clean.
        foreach($this->objectsByPath as $path => $item) {
            if ($item->isModified()) {
                if ($item instanceof \PHPCR\NodeInterface) {
                    foreach ($item->getProperties() as $propertyName => $property) {
                        if ($property->isModified()) {
                            $this->transport->storeProperty($property->getPath(), $property);
                        }
                    }
                } elseif ($item instanceof \PHPCR\PropertyInterface) {
                    if ($item->getNativeValue() === null) {
                        $this->transport->deleteProperty($path);
                    } else {
                        $this->transport->storeProperty($path, $item);
                    }
                } else {
                    throw new \UnexpectedValueException('Unknown type '.get_class($item));
                }
            }
        }

        // TODO: have a davex client method to commit transaction

        // commit changes to the local state
        foreach($this->nodesRemove as $path => $dummy) {
            unset($this->objectsByPath[$path]);
        }
        foreach($this->nodesMove as $src => $dst) {
            $this->objectsByPath[$dst] = $this->objectsByPath[$src];
            unset($this->objectsByPath[$src]);
        }
        foreach($this->nodesAdd as $path => $dummy) {
            $item = $this->getNodeByPath($path);
            $item->confirmSaved();
        }
        foreach($this->objectsByPath as $path => $item) {
            if ($item->isModified()) {
                $item->confirmSaved();
            }
        }

        $this->unsaved = false;
    }

    /**
     * Determine if any object is modified
     *
     * @return boolean False
     */
    public function hasPendingChanges()
    {
        if ($this->unsaved || count($this->nodesAdd) || count($this->nodesMove) || count($this->nodesRemove)) {
            return true;
        }
        foreach($this->objectsByPath as $item) {
            if ($item->isModified()) return true;
        }

        return false;
    }

    /**
     * WRITE: add a node at the specified path
     *
     * @param string $absPath the path to the node, including the node identifier
     * @param string $propertyName optional, property name to delete from the given node's path
     *
     * @throws \PHPCR\ItemExistsException if a node already exists at that path
     */
    public function removeItem($absPath, $propertyName = null)
    {
        if (! isset($this->objectsByPath[$absPath])) {
            throw new \PHPCR\RepositoryException("Internal error: nothing at $absPath");
        }

        //FIXME: same-name-siblings...

        if ($propertyName) {
            $absPath .= $propertyName;
        } else {
            $id = $this->objectsByPath[$absPath]->getIdentifier();
            unset($this->objectsByUuid[$id]);
        }
        unset($this->objectsByPath[$absPath]);
        if (isset($this->nodesAdd[$absPath])) {
            //this is a new unsaved node
            unset($this->nodesAdd[$absPath]);
        } else {
            $this->nodesRemove[$absPath] = 1;
        }
    }

    /**
     * WRITE: move node from source path to destination path
     *
     * @param string $srcAbsPath Absolute path to the source node.
     * @param string $destAbsPath Absolute path to the destination where the node shall be moved to.
     *
     * @throws NotImplementedException
     */
    public function moveItem($srcAbsPath, $destAbsPath)
    {
        $this->nodeMove[$srcAbsPath] = $destAbsPath;
        $this->unsaved = true;

        throw new NotImplementedException('TODO: either push to backend and flush cache or update all relevant nodes and rewrite paths from now on.');
        /*
        FIXME: dispatch everything to backend immediatly (without saving) on move so the backend cares about translating all requests to the new path? how do we know if things are modified after that operation?
        otherwise we have to update all cached objects, tell this item its new path and make it dirty.
        */
    }

    /**
     * WRITE: add an item at the specified path.
     *
     * @param string $absPath the path to the node, including the node identifier
     * @param \PHPCR\ItemInterface $item The item to add.
     *
     * @throws \PHPCR\ItemExistsException if a node already exists at that path
     */
    public function addItem($absPath, \PHPCR\ItemInterface $item)
    {
        if (isset($this->objectsByPath[$absPath])) {
            throw new \PHPCR\ItemExistsException($absPath); //FIXME: same-name-siblings...
        }
        $this->objectsByPath[$absPath] = $item;
        if($item instanceof \PHPCR\NodeInterface) {
            //TODO: determine if we have an identifier.
            $this->objectsByUuid[$item->getIdentifier()] = $absPath;
        }
        $this->nodesAdd[$absPath] = 1;
    }

    /**
     * Implementation specific: Transport is used elsewhere, provide it here for Session
     *
     * @return TransportInterface
     */
    public function getTransport()
    {
        return $this->transport;
    }
}
