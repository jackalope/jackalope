<?php

namespace Jackalope;

use Jackalope\ImportExport\ImportExport;
use Jackalope\Lock\LockManager;
use Jackalope\NodeType\NodeTypeManager;
use Jackalope\Observation\ObservationManager;
use Jackalope\Query\QueryManager;
use Jackalope\Transport\LockingInterface;
use Jackalope\Transport\ObservationInterface;
use Jackalope\Transport\QueryInterface;
use Jackalope\Transport\TransactionInterface;
use Jackalope\Transport\VersioningInterface;
use Jackalope\Transport\WorkspaceManagementInterface;
use Jackalope\Transport\WritingInterface;
use Jackalope\Version\VersionManager;
use PHPCR\Lock\LockManagerInterface;
use PHPCR\Observation\ObservationManagerInterface;
use PHPCR\Transaction\UserTransactionInterface;
use PHPCR\UnsupportedRepositoryOperationException;
use PHPCR\WorkspaceInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
class Workspace implements WorkspaceInterface
{
    /**
     * The factory to instantiate objects.
     *
     * @var FactoryInterface
     */
    protected $factory;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @var UserTransactionInterface
     */
    protected $userTransactionManager = null;

    /**
     * The name of the workspace this workspace represents.
     *
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
     * @var LockManagerInterface
     */
    protected $lockManager;

    /**
     * @var ObservationManagerInterface
     */
    protected $observationManager;

    /**
     * Instantiate a workspace referencing a workspace in the storage.
     *
     * @param FactoryInterface $factory the object factory
     * @param string           $name    the workspace name that is used
     */
    public function __construct(FactoryInterface $factory, Session $session, ObjectManager $objectManager, $name)
    {
        $this->factory = $factory;
        $this->session = $session;
        $this->nodeTypeManager = $this->factory->get(NodeTypeManager::class, [$objectManager, $this->getNamespaceRegistry()]);
        $this->name = $name;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function copy($srcAbsPath, $destAbsPath, $srcWorkspace = null)
    {
        $this->session->getObjectManager()->copyNodeImmediately($srcAbsPath, $destAbsPath, $srcWorkspace);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function cloneFrom($srcWorkspace, $srcAbsPath, $destAbsPath, $removeExisting)
    {
        if (!$this->session->getObjectManager()->getTransport() instanceof WritingInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support writing');
        }

        $this->session
            ->getObjectManager()
            ->cloneFromImmediately($srcWorkspace, $srcAbsPath, $destAbsPath, $removeExisting);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function move($srcAbsPath, $destAbsPath)
    {
        $this->session->getObjectManager()->moveNodeImmediately($srcAbsPath, $destAbsPath);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function removeItem($absPath)
    {
        $this->session->getObjectManager()->removeItemImmediately($absPath);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getLockManager()
    {
        if (!$this->session->getTransport() instanceof LockingInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support locking');
        }

        if (null === $this->lockManager) {
            $this->lockManager = $this->factory->get(
                LockManager::class,
                [
                    $this->session->getObjectManager(),
                    $this->session,
                    $this->session->getTransport(),
                ]
            );
        }

        return $this->lockManager;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getQueryManager()
    {
        if (!$this->session->getTransport() instanceof QueryInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support queries');
        }

        return $this->factory->get(QueryManager::class, [$this->session->getObjectManager()]);
    }

    /**
     * Sets the TransactionManager.
     *
     * Called by the repository if transactions are enabled. Transactions are
     * enabled if this is called with a non-null argument, disabled otherwise.
     *
     * @param UserTransactionInterface $userTransactionManager A UserTransaction object
     *
     * @private
     */
    public function setTransactionManager(UserTransactionInterface $userTransactionManager)
    {
        if (!$this->session->getTransport() instanceof TransactionInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support transactions');
        }

        $this->userTransactionManager = $userTransactionManager;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getTransactionManager()
    {
        if (!$this->session->getTransport() instanceof TransactionInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support transactions');
        }

        if (!$this->userTransactionManager) {
            throw new UnsupportedRepositoryOperationException('Transactions are currently disabled');
        }

        return $this->userTransactionManager;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getNamespaceRegistry()
    {
        if (null === $this->namespaceRegistry) {
            $this->namespaceRegistry = $this->factory->get(NamespaceRegistry::class, [$this->session->getTransport()]);
        }

        return $this->namespaceRegistry;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getNodeTypeManager()
    {
        return $this->nodeTypeManager;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getObservationManager()
    {
        if (!$this->session->getTransport() instanceof ObservationInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support observation');
        }

        if (is_null($this->observationManager)) {
            $this->observationManager = $this->factory->get(
                ObservationManager::class,
                [
                    $this->session,
                    $this->session->getTransport(),
                ]
            );
        }

        return $this->observationManager;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getRepositoryManager()
    {
        throw new UnsupportedRepositoryOperationException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getVersionManager()
    {
        if (!$this->session->getTransport() instanceof VersioningInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support versioning');
        }

        return $this->factory->get(VersionManager::class, [$this->session->getObjectManager()]);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAccessibleWorkspaceNames()
    {
        return $this->session->getTransport()->getAccessibleWorkspaceNames();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function importXML($parentAbsPath, $uri, $uuidBehavior)
    {
        ImportExport::importXML(
            $this->getSession()->getNode($parentAbsPath),
            $this->getNamespaceRegistry(),
            $uri,
            $uuidBehavior
        );
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function createWorkspace($name, $srcWorkspace = null)
    {
        if (!$this->session->getTransport() instanceof WorkspaceManagementInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support workspace management');
        }

        return $this->session->getTransport()->createWorkspace($name, $srcWorkspace);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function deleteWorkspace($name)
    {
        if (!$this->session->getTransport() instanceof WorkspaceManagementInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support workspace management');
        }

        return $this->session->getTransport()->deleteWorkspace($name);
    }
}
