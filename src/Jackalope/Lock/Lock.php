<?php

namespace Jackalope\Lock;

use PHPCR\Lock\LockInterface;

/**
 * {@inheritDoc}
 *
 * @api
 */
class Lock implements LockInterface
{
    /** @var string */
    protected $lockOwner;

    /** @var boolean */
    protected $isDeep;

    /** @var \PHPCR\NodeInterface */
    protected $node;

    /** @var string */
    protected $lockToken;

    /** @var integer */
    protected $secondsRemaining;

    /** @var boolean */
    protected $isLive;

    /** @var boolean */
    protected $isSessionScoped;

    /** @var boolean */
    protected $isLockOwningSession;

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
    * @param boolean $isDeep
    */
    public function setIsDeep($isDeep)
    {
        $this->isDeep = $isDeep;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getNode()
    {
        return $this->node;
    }

    /**
     * @param \PHPCR\NodeInterface $node
     */
    public function setNode(\PHPCR\NodeInterface $node)
    {
        $this->node = $node;
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
        return $this->secondsRemaining;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isLive()
    {
        return $this->isLive;
    }

    /**
    * @param boolean $isLive
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
     * @param boolean $isSessionScoped
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
     * @param boolean $isLockOwningSession
     */
    public function setIsLockOwningSession($isLockOwningSession)
    {
        $this->isLockOwningSession = $isLockOwningSession;
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
}
