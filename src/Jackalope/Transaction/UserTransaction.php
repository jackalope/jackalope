<?php
namespace Jackalope\Transaction;

/**
 * Implement the user transaction manager
 *
 * @author Johannes Stark <starkj@gmx.de>
 *
 * @api
 */
class UserTransaction implements \PHPCR\Transaction\UserTransactionInterface
{
    /**
     * The factory to instantiate objects
     * @var \Jackalope\Factory
     */
    protected $factory;

    /**
     * Instance of an implementation of the \PHPCR\SessionInterface.
     * @var \PHPCR\SessionInterface
     */
    protected $session;

    /**
     * Instance of an implementation of the TransportInterface
     * @var \Jackalope\TransportInterface
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
     * @param object $factory  an object factory implementing get() as
     *      described in \Jackalope\Factory
     * @param \Jackalope\TransportInterface $transport
     * @param \PHPCR\SessionInterface $session
     */
    public function __construct($factory, \Jackalope\TransportInterface $transport, \PHPCR\SessionInterface $session)
    {
        $this->factory = $factory;
        $this->transport = $transport;
        $this->session = $session;
        $this->objectManager = $session->getObjectManager();
    }

    /**
     * Begin new transaction associated with current session.
     *
     * @return void
     *
     * @throws \PHPCR\UnsupportedRepositoryOperationException Thrown if a
     *      transaction is already started. Jackalope does not support nested
     *      transactions.
     *
     * @throws \PHPCR\RepositoryException Thrown if the transaction
     *      implementation encounters an unexpected error condition.
     */
    public function begin()
    {
        if ($this->inTransaction) {
            throw new \PHPCR\UnsupportedRepositoryOperationException("Nested transactions are not supported.");
        }

        $this->objectManager->beginTransaction();
        $this->inTransaction = true;
    }

    /**
     * Commit the transaction associated with the current session to store it
     * persistently.
     *
     * TODO: Make sure RollbackException and AccessDeniedException are thrown
     * by the transport if corresponding problems occur
     *
     * @return void
     *
     * @throws \PHPCR\Transaction\RollbackException Thrown to indicate that the
     *      transaction has been rolled back rather than committed.
     * @throws \PHPCR\AccessDeniedException Thrown to indicate that the
     *      session is not allowed to commit the transaction.
     * @throws \LogicException Thrown if the current
     *      session is not associated with a transaction.
     * @throws \PHPCR\RepositoryException Thrown if the transaction
     *      implementation encounters an unexpected error condition.
     */
    public function commit()
    {
        if (! $this->inTransaction) {
            throw new \LogicException("No transaction to commit.");
        }

        $this->objectManager->commitTransaction();
        $this->inTransaction = false;
    }

    /**
     * Obtain the status if the current session is inside of a transaction or
     * not.
     *
     * @return boolean
     *
     * @throws \PHPCR\RepositoryException Thrown if the transaction
     *      implementation encounters an unexpected error condition.
     */
    public function inTransaction()
    {
        //TODO Is there a way to ask for the transaction status via webdav?
        return $this->inTransaction;
    }

    /**
     * Rollback the transaction associated with the current session.
     *
     * TODO: Make sure AccessDeniedException is thrown by the transport if
     * corresponding problems occur
     *
     * @return void
     *
     * @throws \PHPCR\AccessDeniedException Thrown to indicate that the
     *      application is not allowed to roll back the transaction.
     * @throws \LogicException Thrown if the current
     *      session is not associated with a transaction.
     * @throws \PHPCR\RepositoryException Thrown if the transaction
     *      implementation encounters an unexpected error condition.
     */
    public function rollback()
    {
        if (! $this->inTransaction) {
            throw new \LogicException("No transaction to rollback.");
        }

        $this->objectManager->rollbackTransaction();
        $this->inTransaction = false;
    }

    /**
     * Set a timeout for the transaction.
     *
     * Modify the timeout value that is associated with transactions started by
     * the current application with the begin() method. If not explicitly set,
     * the transaction service uses some default value for the transaction
     * timeout.
     *
     * @param int $seconds The value of the timeout in seconds. If the value is
     *      zero, the transaction service restores the default value. If the
     *      value is negative a RepositoryException is thrown.
     *
     * @return void
     *
     * @throws \PHPCR\RepositoryException Thrown if the transaction
     *      implementation encounters an unexpected error condition.
     */
    public function setTransactionTimeout($seconds = 0)
    {
        if ($seconds < 0) {
            throw new \PHPCR\RepositoryException("Value must be possitiv or 0. ". $seconds ." given.");
        }
        $this->transport->setTransactionTimeout($seconds);
    }
}
