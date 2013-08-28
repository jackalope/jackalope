<?php

namespace Jackalope\Lock;

use PHPCR\Lock\LockInfoInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @author David Buchmann <david@liip.ch>
 */
class LockInfo implements LockInfoInterface
{
    private $isDeep = true;
    private $isSessionScoped = false;
    private $timeoutHint = PHP_INT_MAX;
    private $ownerInfo = null;

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setIsDeep($isDeep)
    {
        $this->isDeep = $isDeep;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getIsDeep()
    {
        return $this->isDeep;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setIsSessionScoped($isSessionScoped)
    {
        $this->isSessionScoped = $isSessionScoped;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getIsSessionScoped()
    {
        return $this->isSessionScoped;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setTimeoutHint($timeoutHint)
    {
        $this->timeoutHint = $timeoutHint;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getTimeoutHint()
    {
        return $this->timeoutHint;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setOwnerInfo($ownerInfo)
    {
        $this->ownerInfo = $ownerInfo;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getOwnerInfo()
    {
        return $this->ownerInfo;
    }
}
