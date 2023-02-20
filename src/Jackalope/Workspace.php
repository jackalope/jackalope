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
use PHPCR\NamespaceRegistryInterface;
use PHPCR\NodeType\NodeTypeManagerInterface;
use PHPCR\Observation\ObservationManagerInterface;
use PHPCR\Query\QueryManagerInterface;
use PHPCR\RepositoryManagerInterface;
use PHPCR\Transaction\UserTransactionInterface;
use PHPCR\UnsupportedRepositoryOperationException;
use PHPCR\Version\VersionManagerInterface;
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
    private FactoryInterface $factory;
    private Session $session;
    private NodeTypeManager $nodeTypeManager;
    private ?UserTransactionInterface $userTransactionManager = null;

    /**
     * The name of the workspace this object represents.
     */
    private string $name;
    private NamespaceRegistry $namespaceRegistry;
    private LockManagerInterface $lockManager;
    private ObservationManagerInterface $observationManager;

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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function copy($srcAbsPath, $destAbsPath, $srcWorkspace = null): void
    {
        $this->session->getObjectManager()->copyNodeImmediately($srcAbsPath, $destAbsPath, $srcWorkspace);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function cloneFrom($srcWorkspace, $srcAbsPath, $destAbsPath, $removeExisting): void
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
    public function move($srcAbsPath, $destAbsPath): void
    {
        $this->session->getObjectManager()->moveNodeImmediately($srcAbsPath, $destAbsPath);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function removeItem($absPath): void
    {
        $this->session->getObjectManager()->removeItemImmediately($absPath);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getLockManager(): LockManagerInterface
    {
        if (!$this->session->getTransport() instanceof LockingInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support locking');
        }

        if (!isset($this->lockManager)) {
            $this->lockManager = $this->factory->get(
                LockManager::class,
                [
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
    public function getQueryManager(): QueryManagerInterface
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
    public function setTransactionManager(UserTransactionInterface $userTransactionManager): void
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
    public function getTransactionManager(): ?UserTransactionInterface
    {
        if (!$this->session->getTransport() instanceof TransactionInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support transactions');
        }

        if (!isset($this->userTransactionManager)) {
            throw new UnsupportedRepositoryOperationException('Transactions are currently disabled');
        }

        return $this->userTransactionManager;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getNamespaceRegistry(): NamespaceRegistryInterface
    {
        if (!isset($this->namespaceRegistry)) {
            $this->namespaceRegistry = $this->factory->get(NamespaceRegistry::class, [$this->session->getTransport()]);
        }

        return $this->namespaceRegistry;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getNodeTypeManager(): NodeTypeManagerInterface
    {
        return $this->nodeTypeManager;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getObservationManager(): ObservationManagerInterface
    {
        if (!$this->session->getTransport() instanceof ObservationInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support observation');
        }

        if (!isset($this->observationManager)) {
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
    public function getRepositoryManager(): RepositoryManagerInterface
    {
        throw new UnsupportedRepositoryOperationException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getVersionManager(): VersionManagerInterface
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
    public function getAccessibleWorkspaceNames(): array
    {
        return $this->session->getTransport()->getAccessibleWorkspaceNames();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function importXML($parentAbsPath, $uri, $uuidBehavior): void
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
    public function createWorkspace($name, $srcWorkspace = null): void
    {
        if (!$this->session->getTransport() instanceof WorkspaceManagementInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support workspace management');
        }

        $this->session->getTransport()->createWorkspace($name, $srcWorkspace);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function deleteWorkspace($name): void
    {
        if (!$this->session->getTransport() instanceof WorkspaceManagementInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support workspace management');
        }

        $this->session->getTransport()->deleteWorkspace($name);
    }
}
