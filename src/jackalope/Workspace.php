<?php
namespace jackalope;

/**
 * A Workspace object represents a view onto a persistent workspace within a
 * repository. This view is defined by the authorization settings of the Session
 * object associated with the Workspace object. Each Workspace object is
 * associated one-to-one with a Session object. The Workspace object can be
 * acquired by calling Session.getWorkspace() on the associated Session object.
 */
class Workspace implements \PHPCR_WorkspaceInterface {
    protected $session;
    protected $nodeTypeManager;
    protected $name;
    protected $namespaceRegistry;

    public function __construct(Session $session, ObjectManager $objectManager, $name) {
        $this->session = $session;
        $this->nodeTypeManager = Factory::get('NodeType\NodeTypeManager', array($objectManager));
        $this->name = $name;
    }

    /**
     * Returns the Session object through which this Workspace object was acquired.
     *
     * @return PHPCR_SessionInterface a Session object.
     * @api
     */
    public function getSession() {
        return $this->session;
    }

    /**
     * Returns the name of the actual persistent workspace represented by this
     * Workspace object. This the name used in Repository->login.
     *
     * @return string the name of this workspace.
     * @api
     */
    public function getName() {
        return $this->name;
    }

    /**
     * not implemented
     */
    public function copy($srcAbsPath, $destAbsPath, $srcWorkspace = NULL) {
        throw new NotImplementedException('Write');
    }

    /**
     * not implemented
     */
     //clone is a reserved keyword in php and may not be used as a function name.
    public function klone($srcWorkspace, $srcAbsPath, $destAbsPath, $removeExisting) {
        throw new NotImplementedException('Write');
    }

    /**
     * not implemented
     */
    public function move($srcAbsPath, $destAbsPath) {
        throw new NotImplementedException('Write');
    }

    /**
     * Returns the LockManager object, through which locking methods are accessed.
     *
     * @return PHPCR_Lock_LockManagerInterface
     * @throws PHPCR_UnsupportedRepositoryOperationException if the implementation does not support locking.
     * @throws PHPCR_RepositoryException if an error occurs.
     * @api
     */
    public function getLockManager() {
        throw new \PHPCR_UnsupportedRepositoryOperationException();
    }

    /**
     * Returns the QueryManager object, through search methods are accessed.
     *
     * @return PHPCR_Query_QueryManagerInterface the QueryManager object.
     * @throws PHPCR_RepositoryException if an error occurs.
     * @api
     */
    public function getQueryManager() {
        return Factory::get('Query\QueryManager', array($this->session->getObjectManager()));
    }

    /**
     * Returns the NamespaceRegistry object, which is used to access the mapping
     * between prefixes and namespaces.
     *
     * @return PHPCR_NamespaceRegistryInterface the NamespaceRegistry.
     * @throws PHPCR_RepositoryException if an error occurs.
     * @api
     */
    public function getNamespaceRegistry() {
        if ($this->namespaceRegistry == false) {
            $this->namespaceRegistry = Factory::get('NamespaceRegistry', array($this->session->getTransport()));
        }
        return $this->namespaceRegistry;
    }

    /**
     * Returns the NodeTypeManager through which node type information can be queried.
     * There is one node type registry per repository, therefore the NodeTypeManager
     * is not workspace-specific; it provides introspection methods for the global,
     * repository-wide set of available node types. In repositories that support it,
     * the NodeTypeManager can also be used to register new node types.
     *
     * @return PHPCR_NodeType_NodeTypeManagerInterface a NodeTypeManager object.
     * @throws PHPCR_RepositoryException if an error occurs.
     * @api
     */
    public function getNodeTypeManager() {
        return $this->nodeTypeManager;
    }

    /**
     * Returns the ObservationManager object.
     *
     * @return PHPCR_Observation_ObservationManagerInterface an ObservationManager object.
     * @throws PHPCR_UnsupportedRepositoryOperationException if the implementation does not support observation.
     * @throws PHPCR_RepositoryException if an error occurs.
     * @api
     */
    public function getObservationManager() {
        throw new \PHPCR_UnsupportedRepositoryOperationException();
    }

    /**
     * Returns the VersionManager object.
     *
     * @return PHPCR_Version_VersionManagerInterface a VersionManager object.
     * @throws PHPCR_UnsupportedRepositoryOperationException if the implementation does not support versioning.
     * @throws PHPCR_RepositoryException if an error occurs.
     * @api
     */
    public function getVersionManager() {
        throw new \PHPCR_UnsupportedRepositoryOperationException();
    }

    /**
     * Returns a string array containing the names of all workspaces in this
     * repository that are accessible to this user, given the Credentials that
     * were used to get the Session to which this Workspace is tied.
     * In order to access one of the listed workspaces, the user performs
     * another Repository.login, specifying the name of the desired workspace,
     * and receives a new Session object.
     *
     * @return array string array of names of accessible workspaces.
     * @throws PHPCR_RepositoryException if an error occurs
     * @api
     */
    public function getAccessibleWorkspaceNames() {
        return $this->session->getTransport()->getAccessibleWorkspaceNames();
    }

    /**
     * not implemented
     */
    public function getImportContentHandler($parentAbsPath, $uuidBehavior) {
        throw new NotImplementedException('Write');
    }

    /**
     * not implemented
     */
    public function importXML($parentAbsPath, $in, $uuidBehavior) {
        throw new NotImplementedException('Write');
    }

    /**
     * Creates a new Workspace with the specified name. The new workspace is
     * empty, meaning it contains only root node.
     *
     * If srcWorkspace is given:
     * Creates a new Workspace with the specified name initialized with a
     * clone of the content of the workspace srcWorkspace. Semantically,
     * this method is equivalent to creating a new workspace and manually
     * cloning srcWorkspace to it; however, this method may assist some
     * implementations in optimizing subsequent Node.update and Node.merge
     * calls between the new workspace and its source.
     *
     * The new workspace can be accessed through a login specifying its name.
     *
     * @param string $name A String, the name of the new workspace.
     * @param string $srcWorkspace The name of the workspace from which the new workspace is to be cloned.
     * @return void
     * @throws PHPCR_AccessDeniedException if the session through which this Workspace object was acquired does not have sufficient access to create the new workspace.
     * @throws PHPCR_UnsupportedRepositoryOperationException if the repository does not support the creation of workspaces.
     * @throws PHPCR_NoSuchWorkspaceException if $srcWorkspace does not exist.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function createWorkspace($name, $srcWorkspace = NULL) {
        throw new \PHPCR_UnsupportedRepositoryOperationException();
    }

    /**
     * Deletes the workspace with the specified name from the repository,
     * deleting all content within it.
     *
     * @param string $name A String, the name of the workspace to be deleted.
     * @return void
     * @throws PHPCR_AccessDeniedException if the session through which this Workspace object was acquired does not have sufficient access to remove the workspace.
     * @throws PHPCR_UnsupportedRepositoryOperationException if the repository does not support the removal of workspaces.
     * @throws PHPCR_NoSuchWorkspaceException if $name does not exist.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function deleteWorkspace($name) {
        throw new \PHPCR_UnsupportedRepositoryOperationException();
    }

}
