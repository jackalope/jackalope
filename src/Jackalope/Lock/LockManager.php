<?php

namespace Jackalope\Lock;

use Jackalope\FactoryInterface;
use Jackalope\Item;
use Jackalope\NotImplementedException;
use Jackalope\Transport\LockingInterface;
use PHPCR\InvalidItemStateException;
use PHPCR\Lock\LockException;
use PHPCR\Lock\LockInfoInterface;
use PHPCR\Lock\LockInterface;
use PHPCR\Lock\LockManagerInterface;
use PHPCR\PathNotFoundException;
use PHPCR\SessionInterface;

/**
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 *
 * @author D. Barsotti <daniel.barsotti@liip.ch>
 */
final class LockManager implements \IteratorAggregate, LockManagerInterface
{
    private SessionInterface $session;

    private LockingInterface $transport;

    /**
     * @var Lock[] node locks indexed by absPath
     */
    private array $locks = [];

    public function __construct(FactoryInterface $factory, SessionInterface $session, LockingInterface $transport)
    {
        $this->session = $session;
        $this->transport = $transport;
    }

    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->getLockTokens());
    }

    /**
     * @api
     */
    public function addLockToken($lockToken): void
    {
        throw new NotImplementedException();
    }

    /**
     * @api
     */
    public function getLock($absPath): LockInterface
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
     * @api
     */
    public function getLockTokens()
    {
        throw new NotImplementedException();
    }

    /**
     * @throws \InvalidArgumentException
     *
     * @api
     */
    public function holdsLock($absPath): bool
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
     * @api
     */
    public function lock($absPath, $isDeep, $isSessionScoped, $timeoutHint = PHP_INT_MAX, $ownerInfo = null): LockInterface
    {
        if (!$isSessionScoped) {
            throw new NotImplementedException('Global scoped locks are not yet implemented in Jackalope. If you create such a lock you might not be able to remove it afterward. For now we deactivated this feature.');
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
        if (Item::STATE_NEW === $state || Item::STATE_MODIFIED === $state) {
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
    public function lockWithInfo($absPath, LockInfoInterface $lockInfo): LockInterface
    {
        return $this->lock(
            $absPath,
            $lockInfo->getIsDeep(),
            $lockInfo->getIsSessionScoped(),
            $lockInfo->getTimeoutHint(),
            $lockInfo->getOwnerInfo()
        );
    }

    /**
     * @api
     */
    public function isLocked($absPath): bool
    {
        if (!$this->session->nodeExists($absPath)) {
            throw new PathNotFoundException("There is no node at '$absPath'");
        }

        return $this->transport->isLocked($absPath);
    }

    /**
     * @api
     */
    public function removeLockToken($lockToken): void
    {
        throw new NotImplementedException();
    }

    /**
     * @api
     */
    public function unlock($absPath): void
    {
        if (!$this->session->nodeExists($absPath)) {
            throw new PathNotFoundException("Unable to unlock unexisting node '$absPath'");
        }

        if (!array_key_exists($absPath, $this->locks)) {
            throw new LockException("Unable to find an active lock for the node '$absPath'");
        }

        $node = $this->session->getNode($absPath);

        $state = $node->getState();
        if (Item::STATE_NEW === $state || Item::STATE_MODIFIED === $state) {
            throw new InvalidItemStateException("Cannot unlock the non-clean node '$absPath': current state = $state");
        }

        $this->transport->unlock($absPath, $this->locks[$absPath]->getLockToken());
        $this->locks[$absPath]->setIsLive(false);
    }

    /**
     * @api
     */
    public function createLockInfo(): LockInfoInterface
    {
        return new LockInfo();
    }

    /**
     * The session logout needs to call this so we are able to release any
     * session based locks that where created through this lock manager.
     *
     * @private
     */
    public function logout(): void
    {
        foreach ($this->locks as $path => $lock) {
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
     * for the locks to get the session to get their root node.
     *
     * @private
     */
    public function getSession(): SessionInterface
    {
        return $this->session;
    }
}
