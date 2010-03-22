<?php

class jackalope_ObjectManager {
    protected $session;
    protected $transport;

    protected $objectsByPath;
    protected $objectsByUuid;

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
     * @return jackalope_Node
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
        return $this->objectsByPath[$path];
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
            throw new jackalope_NotImplementedException();
        } else {
            $path = $this->absolutePath($root, $identifier);
            return $this->getNodeByPath($path);
        }
    }
    
    /**
     * Creates an absolute path from a root and an relative path
     * @param string Root path to append the relative
     * @param string Relative path…
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
     * Replaces unwanted characters and ads leading / trailing slashes
     * @param string the path to normalize
     * @return string normalized path
     */
    protected function normalizePath($path) {
        $path = '/' . $path . '/';
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
}
