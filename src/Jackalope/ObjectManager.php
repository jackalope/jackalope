<?php
/**
 * Class to handle nodes and acting as Unit of Work for write operations using
 * the transport interface.
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
 * For write operations, the object manager acts as the Unit of Work handler:
 * it keeps track which nodes are dirty and updates them with the transport
 * interface.
 *
 * @package jackalope
 * @private
 */
class ObjectManager
{
    /**
     * The factory to instantiate objects
     * @var Factory
     */
    protected $factory;

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
     * @var array   [ class => String][ absPath => \PHPCR\ItemInterface ]
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
     * Contains a list of items to be added to the workspace upon save
     * @var array   [ absPath => 1 ]
     */
    protected $itemsAdd = array();

    /**
     * Contains a list of items to be removed from the workspace upon save
     * @var array   [ absPath => 1 ]
     */
    protected $itemsRemove = array(); //TODO: only nodes can be in this list. call it nodesRemove?

    /**
     * Contains a list of node to be moved in the workspace upon save
     * @var array   [ srcAbsPath => dstAbsPath, .. ]
     */
    protected $nodesMove = array();

    /**
     * Registers the provided parameters as attribute to the instance.
     *
     * @param object $factory  an object factory implementing "get" as described in \Jackalope\Factory
     * @param TransportInterface $transport
     * @param \PHPCR\SessionInterface $session
     */
    public function __construct($factory, TransportInterface $transport, \PHPCR\SessionInterface $session)
    {
        $this->factory = $factory;
        $this->transport = $transport;
        $this->session = $session;
    }

    /**
     * Resolves the real path where the item initially was before moving
     *
     * Checks moved nodes whether any parents (or the node itself) was moved and goes back
     * continuing with the translated path as there can be several moves of the same node.
     *
     * Leaves the path unmodified if it was not moved
     *
     * @param   string  $path The current path we try to access a node from
     * @return  string  The resolved path
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
     * To prevent unnecessary work to be done a register will be written containing already retrieved nodes.
     * Unfortunately there is currently no way to refetch a node once it has been fetched.
     *
     * @param string $absPath The absolute path of the node to fetch.
     * @param string $class The class of node to get. TODO: Is it sane to fetch data separatly for Version and normal Node?
     * @return \PHPCR\Node
     *
     * @throws \PHPCR\ItemNotFoundException If nothing is found at that absolute path
     * @throws \PHPCR\RepositoryException    If the path is not absolute or not well-formed
     */
    public function getNodeByPath($absPath, $class = 'Node')
    {
        $this->verifyAbsolutePath($absPath);
        $absPath = $this->normalizePath($absPath);

        if (!isset($this->objectsByPath[$class])) {
            $this->objectsByPath[$class] = array();
        }
        if (empty($this->objectsByPath[$class][$absPath])) {
            if (isset($this->itemsRemove[$absPath])) {
                throw new \PHPCR\ItemNotFoundException('Path not found (node deleted in current session): ' . $absPath);
            }
            // check whether a parent node was removed
            // OPTIMIZE: this is not very efficient. have a tree structure?
            foreach ($this->itemsRemove as $path=>$dummy) {
                if (strpos($absPath, $path) === 0) {
                    throw new \PHPCR\ItemNotFoundException('Path not found (parent node deleted in current session): ' . $absPath);
                }
            }

            if (isset($this->nodesMove[$absPath])) {
                throw new \PHPCR\ItemNotFoundException('Path not found (moved in current session): ' . $absPath);
            }

            // make sure we fetch the correct path if this node has been moved in a not yet persisted operation
            $fetchPath = $this->resolveBackendPath($absPath);

            $node = $this->factory->get(
                $class,
                array(
                    $this->transport->getNode($fetchPath),
                    $absPath,
                    $this->session,
                    $this
                )
            );
            // TODO: is it always legal to call getIdentifier?
            if ($uuid = $node->getIdentifier()) {
                // map even nodes that are not mix:referenceable, as long as they have a uuid
                $this->objectsByUuid[$uuid] = $absPath;
            }
            $this->objectsByPath[$class][$absPath] = $node;
        }

        return $this->objectsByPath[$class][$absPath];
    }

    /**
     * Get the property identified by an absolute path.
     * Uses the factory to instantiate Property
     *
     * @param string $absPath The absolute path of the property to create.
     * @return \PHPCR\Property
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
        } catch(\PHPCR\PahNotFoundException $e) {
            throw new \PHPCR\ItemNotFoundException($e->getMessage(), $e->getCode(), $e);
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

        if ($this->isUUID($path)) {
            $uuid = $path;
            if (empty($this->objectsByUuid[$uuid])) {
                $finalPath = $this->transport->getNodePathForIdentifier($uuid);
                $this->objectsByUuid[$uuid] = $finalPath;
            } else {
                $finalPath = $this->objectsByUuid[$uuid];
            }
        } else {
            // when we implement Session::setNamespacePrefix to remap a prefix, this should be translated here too.
            // more methods would have to call this
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
     * Makes sure $relPath is absolute, prepending $root if it is not already,
     * then normalizes the path.
     *
     * If $relPath is already absolute, it is just normalized
     *
     * If root is missing or does not start with a slash, a slash will be prepended
     * If $relPath is completely empty, the result will be $root
     *
     * @param string $root base path to prepend to $relPath if it is not already absolute
     * @param string $relPath a relative or absolute path
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
        } else if (strlen($relPath)==0) {
            $relPath = $root;
        }

        return $this->normalizePath($relPath);
    }

    /**
     * Get the node idenfied by an uuid or path or root path and relative path.
     *
     * If you have an absolute path use {@link getNodeByPath()}.
     *
     * @param string $identifier uuid or relative path
     * @param string $root optional root if you are in a node context - not used if $identifier is an uuid
     * @param string $class optional class name for the factory
     *
     * @return \PHPCR\Node The specified Node. if not available, ItemNotFoundException is thrown
     *
     * @throws \PHPCR\ItemNotFoundException If the path was not found
     * @throws \PHPCR\RepositoryException if another error occurs.
     */
    public function getNode($identifier, $root = '/', $class = 'Node')
    {
        if ($this->isUUID($identifier)) {
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
     * Retrieves a binary value
     *
     * @param string $path
     * @return string
     */
    public function getBinaryStream($path)
    {
        return $this->transport->getBinaryStream($this->resolveBackendPath($path));  // path guaranteed to be normalized and absolute
    }

    /**
     * Returns node types named in the array or all types if no filter is given.
     *
     * This is only a proxy to the transport
     *
     * @param array $nodeTypes Empty for all or specify node types by name
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
     * Register node types with the backend.
     *
     * This is only a proxy to the transport
     *
     * @param array $definitions an array of NodeTypeDefinitions
     * @param boolean $allowUpdate whether to fail if node already exists or to update it
     * @return bool true on success
     */
    public function registerNodeTypes($types, $allowUpdate)
    {
        return $this->transport->registerNodeTypes($types, $allowUpdate);
    }

    /**
     * Returns all accessible REFERENCE properties in the workspace that point to the node
     *
     * @param string $path the path of the referenced node
     * @param string $name name of referring REFERENCE properties to be returned; if null then all referring REFERENCEs are returned
     * @return ArrayIterator
     */
    public function getReferences($path, $name = null)
    {
        $references = $this->transport->getReferences($this->resolveBackendPath($path), $name); // path guaranteed to be normalized and absolute
        return $this->pathArrayToPropertiesIterator($references);
    }

    /**
     * Returns all accessible WEAKREFERENCE properties in the workspace that point to the node
     *
     * @param string $path the path of the referenced node
     * @param string $name name of referring WEAKREFERENCE properties to be returned; if null then all referring WEAKREFERENCEs are returned
     * @return ArrayIterator
     */
    public function getWeakReferences($path, $name = null)
    {
        $references = $this->transport->getWeakReferences($this->resolveBackendPath($path), $name); // path guaranteed to be normalized and absolute
        return $this->pathArrayToPropertiesIterator($references);
    }

    /**
     * Transform an array containing properties paths to an ArrayIterator over Property objects
     *
     * @param array $array an array of properties paths
     * @return ArrayIterator
     */
    protected function pathArrayToPropertiesIterator($array)
    {
        $props = array();

        //OPTIMIZE: get all the properties in one request?
        foreach($array as $path) {
            $prop = $this->getPropertyByPath($path); //FIXME: this will break if we have non-persisted move
            $props[] = $prop;
        }

        return new \ArrayIterator($props);
    }

    /**
     * Implementation specific way to register node types from cnd with the backend.
     *
     * This is only a proxy to the transport
     *
     * @param $cnd a string with cnd information
     * @param boolean $allowUpdate whether to fail if node already exists or to update it
     * @return bool true on success
     */
    public function registerNodeTypesCnd($cnd, $allowUpdate)
    {
        return $this->transport->registerNodeTypesCnd($cnd, $allowUpdate);
    }

    /**
     * Verifies the path to be absolute and well-formed.
     *
     * @param string $path the path to verify
     *
     * @return boolean always true, exception if this is not a valid path
     *
     * @throws \PHPCR\RepositoryException if the path is not absolute or well-formed
     */
    protected function verifyAbsolutePath($path)
    {
        if (! ($path && $path[0] == '/')) {
            throw new \PHPCR\RepositoryException('Path is not absolute: ' . $path);
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
        }

        return false;
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
        // TODO: start transaction (see transaction branch)
        // TODO: or even better, adjust transport to accept lists and do a diff request instead of single requests
        // this is extremly unspecific: http://jackrabbit.apache.org/frequently-asked-questions.html#FrequentlyAskedQuestions-HowdoIusetransactionswithJCR?
        // or do we have to bundle everything into one request, make transport layer capable of transaction? http://jackrabbit.apache.org/api/2.1/org/apache/jackrabbit/server/remoting/davex/JcrRemotingServlet.html

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
            if ($item instanceof \PHPCR\NodeInterface) {
                $this->transport->storeNode($item);
            } elseif ($item instanceof \PHPCR\PropertyInterface) {
                $this->transport->storeProperty($item);
            } else {
                throw new \UnexpectedValueException('Unknown type '.get_class($item));
            }
        }

        // loop through cached nodes and commit all dirty and set them to clean.
        if (isset($this->objectsByPath['Node'])) {
            foreach ($this->objectsByPath['Node'] as $path => $item) {
                if ($item->isModified()) {
                    if ($item instanceof \PHPCR\NodeInterface) {
                        foreach ($item->getProperties() as $property) {
                            if ($property->isModified()) {
                                $this->transport->storeProperty($property);
                            }
                        }
                    } elseif ($item instanceof \PHPCR\PropertyInterface) {
                        if ($item->getValue() === null) {
                            $this->transport->deleteProperty($path);
                        } else {
                            $this->transport->storeProperty($item);
                        }
                    } else {
                        throw new \UnexpectedValueException('Unknown type '.get_class($item));
                    }
                }
            }
        }

        // TODO: have a davex client method to commit transaction

        // commit changes to the local state
        foreach ($this->itemsRemove as $path => $dummy) {
            unset($this->objectsByPath['Node'][$path]);
        }

        //clear those lists before reloading the newly added nodes from backend, to avoid collisions
        $this->itemsRemove = array();
        $this->nodesMove = array();

        /* local state is already updated in moveNode
        foreach ($this->nodesMove as $src => $dst) {
            $this->objectsByPath[$dst] = $this->objectsByPath[$src];
            unset($this->objectsByPath[$src]);
        }
         */
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
     * Removes the cache of the predecessor version after the node has been checked in
     *
     * @see VersionManager::checkin
     *
     * @return VersionInterface node version
     */
    public function checkin($absPath)
    {
        $path = $this->transport->checkinItem($absPath); //FIXME: what about pending move operations?
        $node = $this->getNodeByPath($path, "Version\Version");
        $predecessorUuids = $node->getProperty('jcr:predecessors')->getString();
        if (!empty($predecessorUuids[0]) && isset($this->objectsByUuid[$predecessorUuids[0]])) {
            $dirtyPath = $this->objectsByUuid[$predecessorUuids[0]];
            unset($this->objectsByPath['Version\Version'][$dirtyPath]);
            unset($this->objectsByPath['Node'][$dirtyPath]); //FIXME: the node object should be told about this
            unset($this->objectsByUuid[$predecessorUuids[0]]);
        }
        return $node;
    }
    /**
     * Removes the cache of the predecessor version after the node has been checked in
     *
     * @see VersionManager::checkin
     *
     * @return void
     */
    public function checkout($absPath)
    {
        $this->transport->checkoutItem($absPath); //FIXME: what about pending move operations?
    }

    /**
     * Removes the node's cache after it has been restored.
     *
     * TODO: This is incomplete. Needs batch processing to avoid chicken-and-egg problems
     */
    public function restore($removeExisting, $vpath, $absPath)
    {
        if (null !== $absPath
            && (isset($this->objectsByPath['Node'][$absPath]) || isset($this->objectsByPath['Version\Version'][$absPath]))
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
     * @return string uuid of the version history node
     */
    public function getVersionHistory($path)
    {
        return $this->transport->getVersionHistory($this->resolveBackendPath($path));
    }

    /**
     * Determine if any object is modified
     *
     * @return boolean true if any pending changes
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
     * Remove a node or a property.
     *
     * @param string $absPath the path to the node, including the node identifier
     * @param string $propertyName optional, property name to delete from the given node's path instead of deleting the node
     *
     * @throws \PHPCR\RepositoryException If node cannot be found at given path
     */
    public function removeItem($absPath, $propertyName = null)
    {
        // the object is always cached as invocation flow goes through Item::remove() without exception
        if (!isset($this->objectsByPath['Node'][$absPath])) {
            throw new \PHPCR\RepositoryException("Internal error: Item not found in local cache at $absPath");
        }

        // was any parent moved?
        foreach ($this->nodesMove as $dst) {
            if (strpos($dst, $absPath) === 0) {
                // this is MOVE, then DELETE but we dispatch DELETE before MOVE
                // TODO we might could just remove the MOVE and put a DELETE on the previous node :)
                throw new \PHPCR\RepositoryException('Internal error: Deleting ('.$absPath.') will fail because your move is dispatched to the server after the delete');
            }
        }

        //FIXME: same-name-siblings...

        if ($propertyName) {
            $absPath = $this->absolutePath($absPath, $propertyName);
        } else {
            $id = $this->objectsByPath['Node'][$absPath]->getIdentifier();
            unset($this->objectsByUuid[$id]);
        }

        unset($this->objectsByPath['Node'][$absPath]);

        if (isset($this->itemsAdd[$absPath])) {
            //this is a new unsaved node
            unset($this->itemsAdd[$absPath]);
        } else {
            $this->itemsRemove[$absPath] = 1;
        }

    }

    /**
     * Rewrites the path of an item while also updating all children.
     *
     * This applies both to the cache and to the items themselves so
     * they return the correct value on getPath calls.
     *
     * Does some magic detection if for example you ADD a node and then rewrite (MOVE)
     * that exact node then it skips the MOVE and just ADDs to the new place. The return
     * value denotes whether a MOVE must still be dispatched to the backend.
     *
     * @param   string  $curPath    Absolute path of the node to rewrite
     * @param   string  $newPath    The new absolute path
     * @return  bool    Whether dispatching the move to the backend is still required (otherwise we replaced the move with another operation)
     */
    public function rewriteItemPaths($curPath, $newPath)
    {
        $moveRequired = true;

        // update internal references in parent
        $parentCurPath = dirname($curPath);
        $parentNewPath = dirname($newPath);
        if (isset($this->objectsByPath['Node'][$parentCurPath])) {
            $obj = $this->objectsByPath['Node'][$parentCurPath];

            $meth = new \ReflectionMethod('\Jackalope\Node', 'unsetChildNode');
            $meth->setAccessible(true);
            $meth->invokeArgs($obj, array(basename($curPath)));
        }
        if (isset($this->objectsByPath['Node'][$parentNewPath])) {
            $obj = $this->objectsByPath['Node'][$parentNewPath];

            $meth = new \ReflectionMethod('\Jackalope\Node', 'addChildNode');
            $meth->setAccessible(true);
            $meth->invokeArgs($obj, array(basename($newPath)));
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

                    $meth = new \ReflectionMethod('\Jackalope\Item', 'setPath');
                    $meth->setAccessible(true);
                    $meth->invokeArgs($this->objectsByPath['Node'][$newItemPath], array($newItemPath));
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
     * @throws \PHPCR\RepositoryException If node cannot be found at given path
     */
    public function moveNode($srcAbsPath, $destAbsPath)
    {
        if ($this->rewriteItemPaths($srcAbsPath, $destAbsPath)) {
            $this->nodesMove[$srcAbsPath] = $destAbsPath;
        }
    }

    /**
     * Implement the workspace move method. It is dispatched immediately
     *
     * @param string $srcAbsPath the path of the node to be moved.
     * @param string $destAbsPath the location to which the node at srcAbsPath is to be moved.
     *
     * @see Workspace::move
     */
    public function moveNodeImmediately($srcAbsPath, $destAbsPath)
    {
        $this->verifyAbsolutePath($srcAbsPath);
        $this->verifyAbsolutePath($destAbsPath);

        $this->transport->moveNode($srcAbsPath, $destAbsPath);
        $this->rewriteItemPaths($srcAbsPath, $destAbsPath); // update local cache
    }

    /**
     * Implement the workspace copy method. It is dispatched immediately
     *
     * @param string $srcAbsPath the path of the node to be copied.
     * @param string $destAbsPath the location to which the node at srcAbsPath is to be copied in this workspace.
     * @param string $srcWorkspace the name of the workspace from which the copy is to be made.
     *
     * @see Workspace::copy
     */
    public function copyNodeImmediately($srcAbsPath, $destAbsPath, $srcWorkspace)
    {
        $this->verifyAbsolutePath($srcAbsPath);
        $this->verifyAbsolutePath($destAbsPath);

        if ($this->session->nodeExists($destAbsPath)) {
            throw new \PHPCR\ItemExistsException('Node already exists at destination (update-on-copy is currently not supported)');
            // to support this, we would have to update the local cache of nodes as well
        }
        $this->transport->copyNode($srcAbsPath, $destAbsPath, $srcWorkspace);
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
        if (isset($this->objectsByPath['Node'][$absPath])) {
            throw new \PHPCR\ItemExistsException($absPath); //FIXME: same-name-siblings...
        }
        $this->objectsByPath['Node'][$absPath] = $item;
        // a new item never has a uuid, no need to add to objectsByUuid

        $this->itemsAdd[$absPath] = 1;
    }

    /**
     * Return the permissions of the current session on the node given by path.
     * Permission can be of 4 types:
     *      - add_node
     *      - read
     *      - remove
     *      - set_property
     * This function will return an array containing zero, one or more of the above strings.
     *
     * @param type $absPath the path to get permissions
     * @return array of string
     */
    public function getPermissions($absPath)
    {
        return $this->transport->getPermissions($absPath);
    }

    /**
     * Clears the state of the current session
     *
     * Removes all cached objects, planned changes etc. Mostly useful for testing purposes.
     *
     * TODO: this will screw up major, as the user of the api can still have references to nodes
     */
    public function clear()
    {
        $this->objectsByPath = array();
        $this->objectsByUuid = array();
        $this->itemsAdd = array();
        $this->itemsRemove = array();
        $this->nodesMove = array();
    }

    /**
     * Implementation specific: Transport is used elsewhere, provide it here for Session
     *
     * @return TransportInterface
     *
     * @private
     */
    public function getTransport()
    {
        return $this->transport;
    }
}
