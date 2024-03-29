<?php

namespace Jackalope\Transaction;

use Jackalope\FactoryInterface;
use Jackalope\ObjectManager;
use Jackalope\Transport\TransactionInterface;
use PHPCR\RepositoryException;
use PHPCR\Transaction\UserTransactionInterface;
use PHPCR\UnsupportedRepositoryOperationException;

/**
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 * @author Johannes Stark <starkj@gmx.de>
 *
 * @api
 */
final class UserTransaction implements UserTransactionInterface
{
    private FactoryInterface $factory;
    private ObjectManager $objectManager;
    private TransactionInterface $transport;
    private bool $inTransaction = false;

    public function __construct(
        FactoryInterface $factory,
        TransactionInterface $transport,
        ObjectManager $objectManager
    ) {
        $this->transport = $transport;
        $this->objectManager = $objectManager;
    }

    /**
     * @api
     */
    public function begin(): void
    {
        if ($this->inTransaction) {
            throw new UnsupportedRepositoryOperationException('Nested transactions are not supported.');
        }

        $this->objectManager->beginTransaction();
        $this->inTransaction = true;
    }

    /**
     * {@inheritDoc}
     *
     * TODO: Make sure RollbackException and AccessDeniedException are thrown
     * by the transport if corresponding problems occur
     *
     * @api
     */
    public function commit(): void
    {
        if (!$this->inTransaction) {
            throw new \LogicException('No transaction to commit.');
        }

        $this->objectManager->commitTransaction();
        $this->inTransaction = false;
    }

    /**
     * @api
     */
    public function inTransaction(): bool
    {
        // TODO Is there a way to ask for the transaction status via webdav?
        return $this->inTransaction;
    }

    /**
     * {@inheritDoc}
     *
     * TODO: Make sure RollbackException and AccessDeniedException are thrown
     * by the transport if corresponding problems occur
     *
     * @api
     */
    public function rollback(): void
    {
        if (!$this->inTransaction) {
            throw new \LogicException('No transaction to rollback.');
        }

        $this->objectManager->rollbackTransaction();
        $this->inTransaction = false;
    }

    /**
     * @api
     */
    public function setTransactionTimeout($seconds = 0): void
    {
        if ($seconds < 0) {
            throw new RepositoryException('Value must be positive or 0. '.$seconds.' given.');
        }
        $this->transport->setTransactionTimeout($seconds);
    }
}
