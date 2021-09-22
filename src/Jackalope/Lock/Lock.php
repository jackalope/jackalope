<?php

namespace Jackalope\Lock;

use Jackalope\NotImplementedException;
use PHPCR\Lock\LockInterface;
use PHPCR\NodeInterface;
use PHPCR\PathNotFoundException;
use PHPCR\RepositoryException;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
final class Lock implements LockInterface
{
    private LockManager $lockManager;

    private string $lockOwner;

    private bool $isDeep;

    private string $path;

    private string $lockToken;

    private bool $isLive = true;

    private bool $isSessionScoped;

    private bool $isLockOwningSession;

    /**
     * The unix timestamp (seconds since 1970) at which this lock will expire or null to never expire.
     */
    private ?int $expire;

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getLockOwner(): string
    {
        return $this->lockOwner;
    }

    /**
     * @private
     */
    public function setLockOwner(string $owner): void
    {
        $this->lockOwner = $owner;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isDeep(): bool
    {
        return $this->isDeep;
    }

    /**
     * @private
     */
    public function setIsDeep(bool $isDeep): void
    {
        $this->isDeep = $isDeep;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @throws PathNotFoundException
     * @throws RepositoryException
     */
    public function getNode(): NodeInterface
    {
        if (null === $this->path) {
            throw new NotImplementedException();
            // TODO either here or in transport figure out the owning node
            // we might want to delay this until actually requested, as we need to walk up the tree to find the owning node
        }

        return $this->lockManager->getSession()->getNode($this->path);
    }

    /**
     * @param string $path the path to our owning node
     *
     * @private
     */
    public function setNodePath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getLockToken(): string
    {
        return $this->lockToken;
    }

    /**
     * @private
     */
    public function setLockToken(string $token): void
    {
        $this->lockToken = $token;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getSecondsRemaining(): int
    {
        // The timeout does not seem to be correctly implemented in Jackrabbit. Thus we
        // always return the max timeout value
        if (null === $this->expire) {
            return PHP_INT_MAX;
        }

        return $this->expire - time();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isLive(): bool
    {
        if ($this->isLive) {
            $this->isLive = $this->lockManager->isLocked($this->path);
        }

        return $this->isLive;
    }

    /**
     * Can be used by the lock manager to tell the lock its no longer live.
     *
     * @private
     */
    public function setIsLive(bool $isLive): void
    {
        $this->isLive = $isLive;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isSessionScoped(): bool
    {
        return $this->isSessionScoped;
    }

    /**
     * @private
     */
    public function setIsSessionScoped(bool $isSessionScoped): void
    {
        $this->isSessionScoped = $isSessionScoped;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isLockOwningSession(): bool
    {
        return $this->isLockOwningSession;
    }

    /**
     * @private
     */
    public function setIsLockOwningSession(bool $isLockOwningSession): void
    {
        $this->isLockOwningSession = $isLockOwningSession;
    }

    /**
     * Set the lock expire timestamp.
     *
     * Set to null for unknown / infinite timeout
     *
     * @param int $expire timestamp when this lock will expire in seconds of unix epoch
     *
     * @private
     *
     * @see http://ch.php.net/manual/en/function.time.php
     */
    public function setExpireTime(?int $expire): void
    {
        $this->expire = $expire;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function refresh(): void
    {
        throw new NotImplementedException();
    }

    /**
     * Set the lock manager to be used with isLive requests and such.
     *
     * @private
     */
    public function setLockManager(LockManager $lm): void
    {
        $this->lockManager = $lm;
    }
}
