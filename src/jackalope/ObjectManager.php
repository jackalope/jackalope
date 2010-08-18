<?php
/**
 * Implementation specific class that talks to the Transport layer to get nodes
 * and caches every node retrieved to improve performance.
 */
class jackalope_ObjectManager {
    protected $session;
    protected $transport;

    /** mapping of absolutePath => node object */
    protected $objectsByPath = array();
    /**
     * mapping of uuid => absolutePath
     * take care never to put a path in here unless there is a node for that path in objectsByPath
     */
    protected $objectsByUuid = array();

    public function __construct(jackalope_TransportInterface $transport,
                                PHPCR_SessionInterface $session) {
        $this->transport = $transport;
        $this->session = $session;
    }

    /**
     * Get the node identified by an absolute path.
     * Uses the factory to instantiate Node
     *
     * @param string $path The absolute path of the node to create
     * @return PHPCR_Node
     */
    public function getNodeByPath($path) {
        $path = $this->normalizePath($path);
        if (empty($this->objectsByPath[$path])) {
            $this->objectsByPath[$path] = jackalope_Factory::get(
                'Node',
                array(
                    $this->transport->getItem($path),
                    $path,
                    $this->session,
                    $this
                )
            );
        }
        //OPTIMIZE: also save in the uuid array
        return $this->objectsByPath[$path];
    }

    /**
     * Get the property identified by an absolute path.
     * Uses the factory to instantiate Property
     *
     * @param string $path The absolute path of the property to create
     * @return PHPCR_Property
     */
    public function getPropertyByPath($path) {
        $path = $this->normalizePath($path);
        $name = substr($path,strrpos($path,'/')+1); //the property name
        $nodep = substr($path,0,strrpos($path,'/')); //the node this property should be in
        /* OPTIMIZE? instead of fetching the node, we could make Transport provide it with a
         * GET /server/tests/jcr%3aroot/tests_level1_access_base/multiValueProperty/jcr%3auuid
         * (davex getItem uses json, which is not applicable to properties)
         */
        $n = $this->getNodeByPath($nodep);
        return $n->getProperty($name); //throws PathNotFoundException if there is no such property
    }

    /**
     * Get the node idenfied by an uuid or path or root path and relative
     * path. If you have an absolute path use getNodeByPath.
     * @param string uuid or relative path
     * @param string optional root if you are in a node context
     * @return return jackalope_Node
     * @throws PHPCR_ItemNotFoundException If the path was not found
     * @throws PHPCR_RepositoryException if another error occurs.
     */
    public function getNode($identifier, $root = '/'){
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
     * This is only a proxy to the transport it returns all node types if none
     * is given or only the ones given as array.
     * @param array empty for all or selected node types by name
     * @return DOMDoocument containing the nodetype information
     */
    public function getNodeTypes($nodeTypes = array()) {
        return $this->transport->getNodeTypes($nodeTypes);
    }

    /**
     * Get a single nodetype @see getNodeTypes
     * @param string the nodetype you want
     * @return DOMDocument containing the nodetype information
     */
    public function getNodeType($nodeType) {
        return $this->getNodeTypes(array($nodeType));
    }

    /**
     * Creates an absolute path from a root and an relative path
     * @param string Root path to append the relative
     * @param string Relative path
     * @return string Absolute path with . and .. resolved
     */
    protected function absolutePath($root, $relPath) {
        $finalPath = array();
        $path = array_merge(explode('/', '/' . $root), explode('/', '/' . $relPath));
        foreach ($path as $pathPart) {
            switch ($pathPart) {
                case '.':
                case '':
                    break;
                case '..':
                    array_pop($finalPath);
                    break;
                default:
                    array_push($finalPath, $pathPart);
                    break;
            }
        }
        return $this->normalizePath(implode('/', $finalPath));
    }

    /**
     *
     * @param   string  $path   The path to validate
     * @return  bool    TRUE if path is well-formed otherwise FALSE
     */
    protected function isWellFormedPath($path) {
        // TODO: incomplete pattern, see JCR Specs 3.2
        return 1 == preg_match('/^[a-z0-9{}\/#:_^+~*\[\]-]*$/', $path);
        
    }

    /**
     * Replaces unwanted characters and adds leading slash
     * @param string $path the path to normalize
     * @return string normalized path
     * @throws PHPCR_RepositoryException    If the path is not well-formed
     */
    protected function normalizePath($path) {
        $path = '/' . $path;
        if (!$this->isWellFormedPath($path)) {
            throw new PHPCR_RepositoryException('Path is not well-formed (we do not yet match against spec): ' . $path);
        }
        return str_replace('//', '/', $path);
    }

    /**
     * Checks if the string could be a uuid
     * @param string possible uuid
     * @return bool if it looks like a uuid it will return true
     */
    protected function isUUID($id) {
        // UUDID is HEX_CHAR{8}-HEX_CHAR{4}-HEX_CHAR{4}-HEX_CHAR{4}-HEX_CHAR{12}
        if (1 === preg_match('/^[[:xdigit:]]{8}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{12}$/', $id)) {
            return true;
        } else {
            return false;
        }
    }

    /** Implementation specific: Transport is used elsewhere, provide it here for Session */
    public function getTransport() {
        return $this->transport;
    }
}
