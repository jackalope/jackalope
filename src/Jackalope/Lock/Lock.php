<?php

namespace Jackalope\Lock;

use Jackalope\NotImplementedException;
use PHPCR\Lock\LockInterface;
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
class Lock implements LockInterface
{
    /**
     * @var LockManager
     */
    protected $lockManager;

    /**
     * @var string
     */
    protected $lockOwner;

    /**
     * @var bool
     */
    protected $isDeep;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $lockToken;

    /**
     * @var bool
     */
    protected $isLive = true;

    /**
     * @var bool
     */
    protected $isSessionScoped;

    /**
     * @var bool
     */
    protected $isLockOwningSession;

    /**
     * The unix timestamp (seconds since 1970) at which this lock will expire.
     *
     * @var int
     */
    protected $expire;

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getLockOwner()
    {
        return $this->lockOwner;
    }

    /**
     * @param string $owner
     * @private
     */
    public function setLockOwner($owner)
    {
        $this->lockOwner = $owner;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isDeep()
    {
        return $this->isDeep;
    }

    /**
     * @param bool $isDeep
     * @private
     */
    public function setIsDeep($isDeep)
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
    public function getNode()
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
    public function setNodePath($path)
    {
        $this->path = $path;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getLockToken()
    {
        return $this->lockToken;
    }

    /**
     * @param string $token
     * @private
     */
    public function setLockToken($token)
    {
        $this->lockToken = $token;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getSecondsRemaining()
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
    public function isLive()
    {
        if ($this->isLive) {
            $this->isLive = $this->lockManager->isLocked($this->path);
        }

        return $this->isLive;
    }

    /**
     * Can be used by the lock manager to tell the lock its no longer live.
     *
     * @param bool $isLive
     *
     * @private
     */
    public function setIsLive($isLive)
    {
        $this->isLive = $isLive;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isSessionScoped()
    {
        return $this->isSessionScoped;
    }

    /**
     * @param bool $isSessionScoped
     * @private
     */
    public function setIsSessionScoped($isSessionScoped)
    {
        $this->isSessionScoped = $isSessionScoped;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isLockOwningSession()
    {
        return $this->isLockOwningSession;
    }

    /**
     * @param bool $isLockOwningSession
     * @private
     */
    public function setIsLockOwningSession($isLockOwningSession)
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
    public function setExpireTime($expire)
    {
        $this->expire = $expire;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function refresh()
    {
        throw new NotImplementedException();
    }

    /**
     * Set the lock manager to be used with isLive requests and such.
     *
     * @private
     */
    public function setLockManager(LockManager $lm)
    {
        $this->lockManager = $lm;
    }
}
