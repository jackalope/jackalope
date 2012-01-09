<?php

namespace Jackalope\Lock;

use PHPCR\Lock\LockManagerInterface,
    PHPCR\SessionInterface,
    Jackalope\ObjectManager,
    Jackalope\FactoryInterface,
    Jackalope\NotImplementedException,
    Jackalope\Transport\LockingInterface;

/**
 * {@inheritDoc}
 *
 * @api
 *
 * @author D. Barsotti <daniel.barsotti@liip.ch>
 */
class LockManager implements \IteratorAggregate, LockManagerInterface
{
    /**
     * @var \Jackalope\ObjectManager
     */
    protected $objectmanager;

    /**
     * The jackalope object factory for this object
     * @var \Jackalope\Factory
     */
    protected $factory;

    /**
     * @var \PHPCR\SessionInterface
     */
    protected $session;

    /**
     * @var \Jackalope\Transport\LockingInterface
     */
    protected $transport;

    /**
     * Contains a list of nodes locks
     *
     * @var array(absPath => Lock)
     */
    protected $locks = array();

    /**
     * Create the version manager - there should be only one per session.
     *
     * @param \Jackalope\FactoryInterface $factory An object factory implementing "get" as described in \Jackalope\FactoryInterface
     * @param \Jackalope\ObjectManager $objectManager
     * @param \PHPCR\SessionInterface $session
     * @param \Jackalope\Transport\LockingInterface $transport
     * @return \Jackalope\Lock\LockManager
     */
    public function __construct(FactoryInterface $factory, ObjectManager $objectManager, SessionInterface $session, LockingInterface $transport)
    {
        $this->objectmanager = $objectManager;
        $this->factory = $factory;
        $this->session = $session;
        $this->transport = $transport;
    }

    public function getIterator() {
        // TODO: return an iterator over getLockTokens() results
        return new ArrayIterator($this);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function addLockToken($lockToken)
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function getLock($absPath)
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function getLockTokens()
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function holdsLock($absPath)
    {
        if (!$this->session->nodeExists($absPath)) {
            throw new \PHPCR\PathNotFoundException("The node '$absPath' does not exist");
        }

        $node = $this->session->getNode($absPath);

        return $node->isNodeType('mix:lockable')
            && $node->hasProperty('jcr:lockIsDeep')
            && $node->hasProperty('jcr:lockOwner');
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function lock($absPath, $isDeep, $isSessionScoped, $timeoutHint, $ownerInfo)
    {
        // If the node does not exist, Jackrabbit will return an HTTP 412 error which is
        // the same as if the node was not assigned the 'mix:lockable' mixin. To avoid
        // problems in determining which of those error it would be, it's easier to detect
        // non-existing nodes earlier.
        if (!$this->session->nodeExists($absPath)) {
            throw new \PHPCR\PathNotFoundException("Unable to lock unexisting node '$absPath'");
        }

        $node = $this->session->getNode($absPath);

        $state = $node->getState();
        if ($state === \Jackalope\Item::STATE_NEW || $state === \Jackalope\Item::STATE_MODIFIED) {
            throw new \PHPCR\InvalidItemStateException("Cannot lock the non-clean node '$absPath': current state = $state");
        }

        try
        {
            $lock = $this->transport->lockNode($absPath, $isDeep, $isSessionScoped, $timeoutHint, $ownerInfo);
        }
        catch (\PHPCR\RepositoryException $ex)
        {
            // Check if it's a 412 error, otherwise re-throw the same exception
            if (preg_match('/Response \(HTTP 412\):/', $ex->getMessage()))
            {
                throw new \PHPCR\Lock\LockException("Unable to lock the non-lockable node '$absPath': " . $ex->getMessage(), 412);
            }

            // Any other exception will simply be rethrown
            throw $ex;
        }

        // Store the lock for further use
        $this->locks[$absPath] = $lock;

        return $lock;

    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function isLocked($absPath)
    {
        return $this->transport->isLocked($absPath);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function removeLockToken($lockToken)
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function unlock($absPath)
    {
        if (!$this->session->nodeExists($absPath)) {
            throw new \PHPCR\PathNotFoundException("Unable to unlock unexisting node '$absPath'");
        }

        if (!array_key_exists($absPath, $this->locks)) {
            throw new \PHPCR\Lock\LockException("Unable to find an active lock for the node '$absPath'");
        }

        $node = $this->session->getNode($absPath);

        $state = $node->getState();
        if ($state === \Jackalope\Item::STATE_NEW || $state === \Jackalope\Item::STATE_MODIFIED) {
            throw new \PHPCR\InvalidItemStateException("Cannot unlock the non-clean node '$absPath': current state = $state");
        }

        $this->transport->unlock($absPath, $this->locks[$absPath]->getLockToken());
    }
}