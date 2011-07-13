<?php

namespace Jackalope\Transaction;

use Jackalope;
use PHPCR;

/**
 *
 *
 * @api
 */
class UserTransaction implements \PHPCR\Transaction\UserTransactionInterface
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
     * Stores the actual state if the application is inside a transaction or not
     * @var inTransaction
     */
    protected $inTransaction = false;

    /**
     * Registers the provided parameters as attribute to the instance.
     *
     * @param object $factory  an object factory implementing "get" as described in \Jackalope\Factory
     * @param TransportInterface $transport
     * @param \PHPCR\SessionInterface $session
     */
    public function __construct($factory, \Jackalope\TransportInterface $transport, \PHPCR\SessionInterface $session)
    {
        $this->factory = $factory;
        $this->transport = $transport;
        $this->session = $session;
    }

    /**
     * Begin new transaction associated with current session.
     *
     * @return void
     *
     * @throws \PHPCR\UnsupportedRepositoryOperationException Thrown if a transaction
     *      is already started and the transaction implementation or backend does not
     *      support nested transactions.
     *
     * @throws \PHPCR\RepositoryException Thrown if the transaction implementation
     *      encounters an unexpected error condition.
     */
    public function begin()
    {
        if ($this->inTransaction) {
            throw new \PHPCR\UnsupportedRepositoryOperationException("Nested transactions are not supported.");
        }

        $this->transport->beginTransaction();
        $this->inTransaction = true;
    }

    /**
     *
     * Complete the transaction associated with the current session.
     * TODO: Make shure RollbackException and AccessDeniedException are thrown by the transport
     * if corresponding problems occure
     *
     * @return void
     *
     * @throws \PHPCR\Transaction\RollbackException Thrown to indicate that the
     *      transaction has been rolled back rather than committed.
     * @throws \PHPCR\AccessDeniedException Thrown to indicate that the
     *      session is not allowed to commit the transaction.
     * @throws \LogicException Thrown if the current
     *      session is not associated with a transaction.
     * @throws \PHPCR\RepositoryException Thrown if the transaction implementation
     *      encounters an unexpected error condition.
     */
    public function commit()
    {
        if (! $this->inTransaction) {
            throw new \LogicException("No transaction to commit.");
        }

        $this->transport->commitTransaction();
        $this->inTransaction = false;
    }

    /**
     *
     * Obtain the status if the current session is inside of a transaction or not.
     *
     * @return boolean
     *
     * @throws \PHPCR\RepositoryException Thrown if the transaction implementation
     *      encounters an unexpected error condition.
     */
    public function inTransaction()
    {
        //TODO Is there a way to ask for the transaction status via webdav?
        return $this->inTransaction;
    }

    /**
     *
     * Roll back the transaction associated with the current session.
     * TODO: Make shure AccessDeniedException is thrown by the transport
     * if corresponding problems occure
     *
     * @return void
     *
     * @throws \PHPCR\AccessDeniedException Thrown to indicate that the
     *      application is not allowed to roll back the transaction.
     * @throws \LogicException Thrown if the current
     *      session is not associated with a transaction.
     * @throws \PHPCR\RepositoryException Thrown if the transaction implementation
     *      encounters an unexpected error condition.
     */
    public function rollback()
    {
        if (! $this->inTransaction) {
            throw new \LogicException("No transaction to rollback.");
        }

        $this->transport->rollbackTransaction();
        $this->inTransaction = false;
        $this->session->clear();
    }

    /**
     *
     * Modify the timeout value that is associated with transactions started by
     * the current application with the begin method. If an application has not
     * called this method, the transaction service uses some default value for the
     * transaction timeout.
     *
     * @param int $seconds The value of the timeout in seconds. If the value is zero,
     *      the transaction service restores the default value. If the value is
     *      negative a RepositoryException is thrown.
     *
     * @return void
     *
     * @throws \PHPCR\RepositoryException Thrown if the transaction implementation
     *      encounters an unexpected error condition.
     */
    public function setTransactionTimeout($seconds = 0)
    {
        if ($seconds < 0) {
            throw new \PHPCR\RepositoryException("Value must be possitiv or 0. ". $seconds ." given.");
        }
        $this->transport->setTransactionTimeout($seconds);
    }
}
