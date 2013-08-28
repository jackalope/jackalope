<?php

namespace Jackalope\Lock;

use ArrayIterator;
use IteratorAggregate;

use PHPCR\SessionInterface;
use PHPCR\PathNotFoundException;
use PHPCR\InvalidItemStateException;
use PHPCR\Lock\LockManagerInterface;
use PHPCR\Lock\LockInfoInterface;
use PHPCR\Lock\LockException;

use Jackalope\ObjectManager;
use Jackalope\FactoryInterface;
use Jackalope\Item;
use Jackalope\Transport\LockingInterface;
use Jackalope\NotImplementedException;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 *
 * @author D. Barsotti <daniel.barsotti@liip.ch>
 */
class LockManager implements IteratorAggregate, LockManagerInterface
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
     * @var Lock[] indexed by absPath
     */
    protected $locks = array();

    /**
     * Create the version manager - there should be only one per session.
     *
     * @param  \Jackalope\FactoryInterface           $factory       An object factory implementing "get" as described in \Jackalope\FactoryInterface
     * @param  \Jackalope\ObjectManager              $objectManager
     * @param  \PHPCR\SessionInterface               $session
     * @param  \Jackalope\Transport\LockingInterface $transport
     * @return \Jackalope\Lock\LockManager
     */
    public function __construct(FactoryInterface $factory, ObjectManager $objectManager, SessionInterface $session, LockingInterface $transport)
    {
        $this->objectmanager = $objectManager;
        $this->factory = $factory;
        $this->session = $session;
        $this->transport = $transport;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->getLockTokens());
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function addLockToken($lockToken)
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getLock($absPath)
    {
        // The locks are only cached in the LockManager if the lock was created
        // by him. Otherwise we don't have the Lock cached.

        // Also see: https://issues.apache.org/jira/browse/JCR-2029
        // About needing to fetch multiple nodes of a locked subtree to get the lock owner.

        // TODO:
        // If i'm the owner and the lock is in cache then return it
        // else do a propfind on jackrabbit (see isLocked) and return the
        // resulting lock

        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getLockTokens()
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function holdsLock($absPath)
    {
        if (!$this->session->nodeExists($absPath)) {
            throw new PathNotFoundException("The node '$absPath' does not exist");
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
    public function lock($absPath, $isDeep, $isSessionScoped, $timeoutHint = PHP_INT_MAX, $ownerInfo = null)
    {
        if (!$isSessionScoped) {
            throw new NotImplementedException("Global scoped locks are not yet implemented in Jackalope. If you create such a lock you might not be able to remove it afterward. For now we deactivated this feature.");
        }

        // If the node does not exist, Jackrabbit will return an HTTP 412 error which is
        // the same as if the node was not assigned the 'mix:lockable' mixin. To avoid
        // problems in determining which of those error it would be, it's easier to detect
        // non-existing nodes earlier.
        if (!$this->session->nodeExists($absPath)) {
            throw new PathNotFoundException("Unable to lock unexisting node '$absPath'");
        }

        $node = $this->session->getNode($absPath);

        $state = $node->getState();
        if ($state === Item::STATE_NEW || $state === Item::STATE_MODIFIED) {
            throw new InvalidItemStateException("Cannot lock the non-clean node '$absPath': current state = $state");
        }

        $lock = $this->transport->lockNode($absPath, $isDeep, $isSessionScoped, $timeoutHint, $ownerInfo);
        $lock->setLockManager($this);

        // Store the lock for further use
        $this->locks[$absPath] = $lock;

        return $lock;
    }

    /**
     * {@inheritDoc}
     *
     * Convenience method forwarding to lock()
     *
     * @api
     */
    public function lockWithInfo($absPath, LockInfoInterface $lockInfo)
    {
        return $this->lock(
            $absPath,
            $lockInfo->getIsDeep(),
            $lockInfo->getIsSessionScoped(),
            $lockInfo->getTimeOutHint(),
            $lockInfo->getOwnerInfo()
        );
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isLocked($absPath)
    {
        if (!$this->session->nodeExists($absPath)) {
            throw new PathNotFoundException("There is no node at '$absPath'");
        }

        return $this->transport->isLocked($absPath);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function removeLockToken($lockToken)
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function unlock($absPath)
    {
        if (!$this->session->nodeExists($absPath)) {
            throw new PathNotFoundException("Unable to unlock unexisting node '$absPath'");
        }

        if (!array_key_exists($absPath, $this->locks)) {
            throw new LockException("Unable to find an active lock for the node '$absPath'");
        }

        $node = $this->session->getNode($absPath);

        $state = $node->getState();
        if ($state === Item::STATE_NEW || $state === Item::STATE_MODIFIED) {
            throw new InvalidItemStateException("Cannot unlock the non-clean node '$absPath': current state = $state");
        }

        $this->transport->unlock($absPath, $this->locks[$absPath]->getLockToken());
        $this->locks[$absPath]->setIsLive(false);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function createLockInfo()
    {
        return new LockInfo;
    }

    /**
     * The session logout needs to call this so we are able to release any
     * session based locks that where created through this lock manager.
     *
     * @private
     */
    public function logout()
    {
        foreach ($this->locks as $path => $lock) {;
            if ($lock->isSessionScoped() && $lock->isLockOwningSession()) {
                try {
                    $this->unlock($path); // will tell the lock its no longer live
                } catch (\Exception $e) {
                    // ignore exceptions here, we don't care too much. would be nice to log though
                }
            }
        }
    }

    /**
     * for the locks to get the session to get their root node
     *
     * @return SessionInterface
     *
     * @private
     */
    public function getSession()
    {
        return $this->session;
    }
}
