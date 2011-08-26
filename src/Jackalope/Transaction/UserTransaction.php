<?php
namespace Jackalope\Transaction;

// inherit all doc
/**
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

    // inherit all doc
    /**
     * @api
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
            throw new \LogicException("No transaction to commit.");
        }

        $this->objectManager->commitTransaction();
        $this->inTransaction = false;
    }

    // inherit all doc
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

    // inherit all doc
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
            throw new \LogicException("No transaction to rollback.");
        }

        $this->objectManager->rollbackTransaction();
        $this->inTransaction = false;
    }

    // inherit all doc
    /**
     * @api
     */
    public function setTransactionTimeout($seconds = 0)
    {
        if ($seconds < 0) {
            throw new \PHPCR\RepositoryException("Value must be possitiv or 0. ". $seconds ." given.");
        }
        $this->transport->setTransactionTimeout($seconds);
    }
}
