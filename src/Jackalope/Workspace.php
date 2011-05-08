<?php
namespace Jackalope;

/**
 * A Workspace object represents a view onto a persistent workspace within a
 * repository. This view is defined by the authorization settings of the Session
 * object associated with the Workspace object. Each Workspace object is
 * associated one-to-one with a Session object. The Workspace object can be
 * acquired by calling Session.getWorkspace() on the associated Session object.
 */
class Workspace implements \PHPCR\WorkspaceInterface
{
    /**
     * The factory to instantiate objects
     * @var Factory
     */
    protected $factory;

    protected $session;
    protected $nodeTypeManager;
    protected $name;
    protected $namespaceRegistry;

    public function __construct($factory, Session $session, ObjectManager $objectManager, $name)
    {
        $this->factory = $factory;
        $this->session = $session;
        $this->nodeTypeManager = $this->factory->get('NodeType\NodeTypeManager', array($objectManager));
        $this->name = $name;
    }

    /**
     * Returns the Session object through which this Workspace object was acquired.
     *
     * @return \PHPCR\SessionInterface a Session object.
     * @api
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Returns the name of the actual persistent workspace represented by this
     * Workspace object. This the name used in Repository->login.
     *
     * @return string the name of this workspace.
     * @api
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Copies a Node including its children to a new location to the given workspace.
     *
     * This method copies the subgraph rooted at, and including, the node at
     * $srcWorkspace (if given) and $srcAbsPath to the new location in this
     * Workspace at $destAbsPath.
     *
     * This is a workspace-write operation and therefore dispatches changes
     * immediately and does not require a save.
     *
     * When a node N is copied to a path location where no node currently
     * exists, a new node N' is created at that location.
     * The subgraph rooted at and including N' (call it S') is created and is
     * identical to the subgraph rooted at and including N (call it S) with the
     * following exceptions:
     * * Every node in S' is given a new and distinct identifier
     *   - or if $srcWorkspace is given -
     *   Every referenceable node in S' is given a new and distinct identifier
     *   while every non-referenceable node in S' may be given a new and
     *   distinct identifier.
     * * The repository may automatically drop any mixin node type T present on
     *   any node M in S. Dropping a mixin node type in this context means that
     *   while M remains unchanged, its copy M' will lack the mixin T and any
     *   child nodes and properties defined by T that are present on M. For
     *   example, a node M that is mix:versionable may be copied such that the
     *   resulting node M' will be a copy of N except that M' will not be
     *   mix:versionable and will not have any of the properties defined by
     *   mix:versionable. In order for a mixin node type to be dropped it must
     *   be listed by name in the jcr:mixinTypes property of M. The resulting
     *   jcr:mixinTypes property of M' will reflect any change.
     * * If a node M in S is referenceable and its mix:referenceable mixin is
     *   not dropped on copy, then the resulting jcr:uuid property of M' will
     *   reflect the new identifier assigned to M'.
     * * Each REFERENCE or WEAKEREFERENCE property R in S is copied to its new
     *   location R' in S'. If R references a node M within S then the value of
     *   R' will be the identifier of M', the new copy of M, thus preserving the
     *   reference within the subgraph.
     *
     * When a node N is copied to a location where a node N' already exists, the
     * repository may either immediately throw an ItemExistsException or attempt
     * to update the node N' by selectively replacing part of its subgraph with
     * a copy of the relevant part of the subgraph of N. If the node types of N
     * and N' are compatible, the implementation supports update-on-copy for
     * these node types and no other errors occur, then the copy will succeed.
     * Otherwise an ItemExistsException is thrown.
     *
     * Which node types can be updated on copy and the details of any such
     * updates are implementation-dependent. For example, some implementations
     * may support update-on-copy for mix:versionable nodes. In such a case the
     * versioning-related properties of the target node would remain unchanged
     * (jcr:uuid, jcr:versionHistory, etc.) while the substantive content part
     * of the subgraph would be replaced with that of the source node.
     *
     * The $destAbsPath provided must not have an index on its final element. If
     * it does then a RepositoryException is thrown. Strictly speaking, the
     * $destAbsPath parameter is actually an absolute path to the parent node of
     * the new location, appended with the new name desired for the copied node.
     * It does not specify a position within the child node ordering. If ordering
     * is supported by the node type of the parent node of the new location, then
     * the new copy of the node is appended to the end of the child node list.
     *
     * This method cannot be used to copy an individual property by itself. It
     * copies an entire node and its subgraph (including, of course, any
     * properties contained therein).
     *
     * @param string $srcAbsPath the path of the node to be copied.
     * @param string $destAbsPath the location to which the node at srcAbsPath is to be copied in this workspace.
     * @param string $srcWorkspace the name of the workspace from which the copy is to be made.
     * @return void
     *
     * @throws \PHPCR\NoSuchWorkspaceException if srcWorkspace does not exist or if the current Session does not have permission to access it.
     * @throws \PHPCR\ConstraintViolationException if the operation would violate a node-type or other implementation-specific constraint
     * @throws \PHPCR\Version\VersionException if the parent node of destAbsPath is read-only due to a checked-in node.
     * @throws \PHPCR\AccessDeniedException if the current session does have access srcWorkspace but otherwise does not have sufficient access to complete the operation.
     * @throws \PHPCR\PathNotFoundException if the node at srcAbsPath in srcWorkspace or the parent of destAbsPath in this workspace does not exist.
     * @throws \PHPCR\ItemExistsException if a node already exists at destAbsPath and either same-name siblings are not allowed or update on copy is not supported for the nodes involved.
     * @throws \PHPCR\Lock\LockException if a lock prevents the copy.
     * @throws \PHPCR\RepositoryException if the last element of destAbsPath has an index or if another error occurs.
     * @api
     */
    public function copy($srcAbsPath, $destAbsPath, $srcWorkspace = null)
    {

        if (!Helper::isAbsolutePath($srcAbsPath) || !Helper::isAbsolutePath($destAbsPath)) {
            throw new \PHPCR\RepositoryException('Source and destination paths must be absolute');
        }
        if ($this->session->nodeExists($destAbsPath)) {
            throw new \PHPCR\ItemExistsException('Node already exists at destination (update-on-copy is currently not supported)');
        }

        $this->session->getObjectManager()->getTransport()->copyNode($srcAbsPath, $destAbsPath, $srcWorkspace);
    }

    /**
     * not implemented
     */
     //clone is a reserved keyword in php and may not be used as a function name.
    public function cloneFrom($srcWorkspace, $srcAbsPath, $destAbsPath, $removeExisting)
    {
        throw new NotImplementedException('Write');
        /* @param boolean $removeExisting if false then this method throws an ItemExistsException on identifier conflict
         *                                with an incoming node. If true then a identifier conflict is resolved by removing
         *                                the existing node from its location in this workspace and cloning (copying in) the
         *                                one from srcWorkspace.
         *
         * IMPLEMENT THIS CHECK HERE
         */
    }

    /**
     * Moves the node at srcAbsPath (and its entire subgraph) to the new location at destAbsPath.
     *
     * If successful, the change is persisted immediately, there is no need to
     * call save. Note that this is in contrast to
     * Session->move($srcAbsPath, $destAbsPath) which operates within the transient
     * space and hence requires a save.
     *
     * The identifiers of referenceable nodes must not be changed by a move. The
     * identifiers of non-referenceable nodes may change.
     *
     * The destAbsPath provided must not have an index on its final element. If
     * it does then a RepositoryException is thrown. Strictly speaking, the
     * destAbsPath parameter is actually an absolute path to the parent node of
     * the new location, appended with the new name desired for the moved node.
     * It does not specify a position within the child node ordering. If ordering
     * is supported by the node type of the parent node of the new location, then
     * the newly moved node is appended to the end of the child node list.
     *
     * This method cannot be used to move just an individual property by itself.
     * It moves an entire node and its subgraph (including, of course, any
     * properties contained therein).
     *
     * The identifiers of referenceable nodes must not be changed by a move. The
     * identifiers of non-referenceable nodes may change.
     *
     * @param string $srcAbsPath the path of the node to be moved.
     * @param string $destAbsPath the location to which the node at srcAbsPath is to be moved.
     * @return void
     *
     * @throws \PHPCR\ConstraintViolationException if the operation would violate a node-type or other
     *                                             implementation-specific constraint
     * @throws \PHPCR\Version\VersionException if the parent node of destAbsPath is read-only due to a checked-in node.
     * @throws \PHPCR\AccessDeniedException if the current session (i.e. the session that was used to acquire this
     *                                      Workspace object) does not have sufficient access rights to complete the
     *                                      operation.
     * @throws \PHPCR\PathNotFoundException if the node at srcAbsPath or the parent of destAbsPath does not exist.
     * @throws \PHPCR\ItemExistsException if a node already exists at destAbsPath and same-name siblings are not allowed.
     * @throws \PHPCR\Lock\LockException if a lock prevents the move.
     * @throws \PHPCR\RepositoryException if the last element of destAbsPath has an index or if another error occurs.
     * @api
     */
    public function move($srcAbsPath, $destAbsPath)
    {
        if (!Helper::isAbsolutePath($srcAbsPath) || !Helper::isAbsolutePath($destAbsPath)) {
            throw new \PHPCR\RepositoryException('Source and destination paths must be absolute');
        }
        $this->session->getObjectManager()->getTransport()->moveNode($srcAbsPath, $destAbsPath);
        $this->session->getObjectManager()->rewriteItemPaths($srcAbsPath, $destAbsPath); // update local cache
    }

    /**
     * Returns the LockManager object, through which locking methods are accessed.
     *
     * @return \PHPCR\Lock\LockManagerInterface
     * @throws \PHPCR\UnsupportedRepositoryOperationException if the implementation does not support locking.
     * @throws \PHPCR\RepositoryException if an error occurs.
     * @api
     */
    public function getLockManager()
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }

    /**
     * Returns the QueryManager object, through search methods are accessed.
     *
     * @return \PHPCR\Query\QueryManagerInterface the QueryManager object.
     * @throws \PHPCR\RepositoryException if an error occurs.
     * @api
     */
    public function getQueryManager()
    {
        return $this->factory->get('Query\QueryManager', array($this->session->getObjectManager()));
    }

    /**
     * Returns the NamespaceRegistry object, which is used to access the mapping
     * between prefixes and namespaces.
     *
     * @return \PHPCR\NamespaceRegistryInterface the NamespaceRegistry.
     * @throws \PHPCR\RepositoryException if an error occurs.
     * @api
     */
    public function getNamespaceRegistry()
    {
        if ($this->namespaceRegistry == false) {
            $this->namespaceRegistry = $this->factory->get('NamespaceRegistry', array($this->session->getTransport()));
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
     * @return \PHPCR\NodeType\NodeTypeManagerInterface a NodeTypeManager object.
     * @throws \PHPCR\RepositoryException if an error occurs.
     * @api
     */
    public function getNodeTypeManager()
    {
        return $this->nodeTypeManager;
    }

    /**
     * Returns the ObservationManager object.
     *
     * @return \PHPCR\Observation\ObservationManagerInterface an ObservationManager object.
     * @throws \PHPCR\UnsupportedRepositoryOperationException if the implementation does not support observation.
     * @throws \PHPCR\RepositoryException if an error occurs.
     * @api
     */
    public function getObservationManager()
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }

    /**
     * Returns the VersionManager object.
     *
     * @return \PHPCR\Version\VersionManagerInterface a VersionManager object.
     * @throws \PHPCR\UnsupportedRepositoryOperationException if the implementation does not support versioning.
     * @throws \PHPCR\RepositoryException if an error occurs.
     * @api
     */
    public function getVersionManager()
    {
        return $this->factory->get('Version\VersionManager', array($this->session->getObjectManager()));
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
     * @throws \PHPCR\RepositoryException if an error occurs
     * @api
     */
    public function getAccessibleWorkspaceNames()
    {
        return $this->session->getTransport()->getAccessibleWorkspaceNames();
    }

    /**
     * not implemented
     */
    public function getImportContentHandler($parentAbsPath, $uuidBehavior)
    {
        throw new NotImplementedException('Write');
    }

    /**
     * not implemented
     */
    public function importXML($parentAbsPath, $in, $uuidBehavior)
    {
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
     * @throws \PHPCR\AccessDeniedException if the session through which this Workspace object was acquired does not have sufficient access to create the new workspace.
     * @throws \PHPCR\UnsupportedRepositoryOperationException if the repository does not support the creation of workspaces.
     * @throws \PHPCR\NoSuchWorkspaceException if $srcWorkspace does not exist.
     * @throws \PHPCR\RepositoryException if another error occurs.
     * @api
     */
    public function createWorkspace($name, $srcWorkspace = null)
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }

    /**
     * Deletes the workspace with the specified name from the repository,
     * deleting all content within it.
     *
     * @param string $name A String, the name of the workspace to be deleted.
     * @return void
     * @throws \PHPCR\AccessDeniedException if the session through which this Workspace object was acquired does not have sufficient access to remove the workspace.
     * @throws \PHPCR\UnsupportedRepositoryOperationException if the repository does not support the removal of workspaces.
     * @throws \PHPCR\NoSuchWorkspaceException if $name does not exist.
     * @throws \PHPCR\RepositoryException if another error occurs.
     * @api
     */
    public function deleteWorkspace($name)
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }

}
