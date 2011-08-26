<?php
namespace Jackalope;

// inherit all doc
/**
 * @api
 */
class Workspace implements \PHPCR\WorkspaceInterface
{
    /**
     * The factory to instantiate objects
     * @var Factory
     */
    protected $factory;
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var Jackalope\NodeType\NodeTypeManager
     */
    protected $nodeTypeManager;
    /**
     * @var \PHPCR\Transaction\UserTransactionInterface
     */
    protected $utx = null;
    /**
     * The name of the workspace this workspace represents
     * @var string
     */
    protected $name;
    /**
     * The namespace registry.
     *
     * @var NamespaceRegistry
     */
    protected $namespaceRegistry;

    /**
     * Instantiate a workspace referencing a workspace in the storage.
     *
     * @param object $factory an object factory implementing "get" as
     *      described in \Jackalope\Factory
     * @param Session $session
     * @param ObjectManager $objectManager
     * @param string $name the workspace name that is used
     */
    public function __construct($factory, Session $session, ObjectManager $objectManager, $name)
    {
        $this->factory = $factory;
        $this->session = $session;
        $this->nodeTypeManager = $this->factory->get('NodeType\NodeTypeManager', array($objectManager));
        $this->name = $name;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getSession()
    {
        return $this->session;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getName()
    {
        return $this->name;
    }

    // inherit all doc
    /**
     * @api
     */
    public function copy($srcAbsPath, $destAbsPath, $srcWorkspace = null)
    {
        $this->session->getObjectManager()->copyNodeImmediately($srcAbsPath, $destAbsPath, $srcWorkspace);
    }

    // inherit all doc
    /**
     * {@inheritDoc}
     *
     * TODO: Implement
     * @api
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

    // inherit all doc
    /**
     * @api
     */
    public function move($srcAbsPath, $destAbsPath)
    {
        $this->session->getObjectManager()->moveNodeImmediately($srcAbsPath, $destAbsPath);
    }

    // inherit all doc
    /**
     * Locking is not implemented in Jackalope
     *
     * @api
     */
    public function getLockManager()
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }

    // inherit all doc
    /**
     * @api
     */
    public function getQueryManager()
    {
        return $this->factory->get('Query\QueryManager', array($this->session->getObjectManager()));
    }

    /**
     * Sets the TransactionManager
     *
     * Called by the repository if transactions are enabled. Transactions are
     * enabled if this is called with a non-null argument, disabled otherwise.
     *
     * @param \PHPCR\Transaction\UserTransactionInterface $utx A
     *      UserTransaction object
     */
    public function setTransactionManager(\PHPCR\Transaction\UserTransactionInterface $utx)
    {
        $this->utx = $utx;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getTransactionManager()
    {
        return $this->utx;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getNamespaceRegistry()
    {
        if ($this->namespaceRegistry == false) {
            $this->namespaceRegistry = $this->factory->get('NamespaceRegistry', array($this->session->getTransport()));
        }
        return $this->namespaceRegistry;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getNodeTypeManager()
    {
        return $this->nodeTypeManager;
    }

    /**
     * Observation is not supported in Jackalope
     *
     * @api
     */
    public function getObservationManager()
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }

    // inherit all doc
    /**
     * @api
     */
    public function getVersionManager()
    {
        return $this->factory->get('Version\VersionManager', array($this->session->getObjectManager()));
    }

    // inherit all doc
    /**
     * @api
     */
    public function getAccessibleWorkspaceNames()
    {
        return $this->session->getTransport()->getAccessibleWorkspaceNames();
    }

    /**
     * Importing is not implemented in jackalope
     *
     * @api
     */
    public function importXML($parentAbsPath, $in, $uuidBehavior)
    {
        throw new NotImplementedException('Write');
    }

    // inherit all doc
    /**
     * @api
     */
    public function createWorkspace($name, $srcWorkspace = null)
    {
        return $this->session->getTransport()->createWorkspace($name, $srcWorkspace);
    }

    // inherit all doc
    /**
     * Deleting workspaces is not implemented in Jackalope
     *
     * @api
     */
    public function deleteWorkspace($name)
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException();
    }
}
