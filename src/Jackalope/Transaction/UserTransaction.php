<?php

namespace Jackalope\Transaction;

use PHPCR\Transaction\UserTransactionInterface;
use PHPCR\UnsupportedRepositoryOperationException;
use PHPCR\RepositoryException;
use PHPCR\SessionInterface;

use Jackalope\Transport\TransactionInterface;
use Jackalope\FactoryInterface;
use Jackalope\ObjectManager;

use LogicException;

/**
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @author Johannes Stark <starkj@gmx.de>
 *
 * @api
 */
class UserTransaction implements UserTransactionInterface
{
    /**
     * The factory to instantiate objects
     * @var FactoryInterface
     */
    protected $factory;

    /**
     * Instance of an implementation of the \PHPCR\SessionInterface.
     * @var \PHPCR\SessionInterface
     */
    protected $session;

    /**
     * @var \Jackalope\ObjectManager
     */
    protected $objectManager;

    /**
     * Instance of an implementation of the TransactionInterface transport
     * @var \Jackalope\Transport\TransactionInterface
     */
    protected $transport;

    /**
     * Stores the current state of the application, whether it is inside a
     * transaction or not
     * @var bool
     */
    protected $inTransaction = false;

    /**
     * Registers the provided parameters as attribute to the instance.
     *
     * @param FactoryInterface   $factory   the object factory
     * @param TransportInterface $transport
     * @param SessionInterface   $session
     */
    public function __construct(FactoryInterface $factory, TransactionInterface $transport,
                                SessionInterface $session, ObjectManager $om)
    {
        $this->factory = $factory;
        $this->transport = $transport;
        $this->session = $session;
        $this->objectManager = $om;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function begin()
    {
        if ($this->inTransaction) {
            throw new UnsupportedRepositoryOperationException("Nested transactions are not supported.");
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
    public function commit()
    {
        if (! $this->inTransaction) {
            throw new LogicException("No transaction to commit.");
        }

        $this->objectManager->commitTransaction();
        $this->inTransaction = false;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function inTransaction()
    {
        //TODO Is there a way to ask for the transaction status via webdav?
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
    public function rollback()
    {
        if (! $this->inTransaction) {
            throw new LogicException("No transaction to rollback.");
        }

        $this->objectManager->rollbackTransaction();
        $this->inTransaction = false;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setTransactionTimeout($seconds = 0)
    {
        if ($seconds < 0) {
            throw new RepositoryException("Value must be positive or 0. ". $seconds ." given.");
        }
        $this->transport->setTransactionTimeout($seconds);
    }
}
