<?php

namespace Jackalope\Lock;

use PHPCR\Lock\LockInfoInterface;

/**
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 * @author David Buchmann <david@liip.ch>
 */
final class LockInfo implements LockInfoInterface
{
    private bool $isDeep = true;

    private bool $isSessionScoped = false;

    private int $timeoutHint = PHP_INT_MAX;

    private ?string $ownerInfo = null;

    /**
     * @api
     */
    public function setIsDeep($isDeep): self
    {
        $this->isDeep = $isDeep;

        return $this;
    }

    /**
     * @api
     */
    public function getIsDeep(): bool
    {
        return $this->isDeep;
    }

    /**
     * @api
     */
    public function setIsSessionScoped($isSessionScoped): self
    {
        $this->isSessionScoped = $isSessionScoped;

        return $this;
    }

    /**
     * @api
     */
    public function getIsSessionScoped(): bool
    {
        return $this->isSessionScoped;
    }

    /**
     * @api
     */
    public function setTimeoutHint($timeoutHint): self
    {
        $this->timeoutHint = $timeoutHint;

        return $this;
    }

    /**
     * @api
     */
    public function getTimeoutHint(): int
    {
        return $this->timeoutHint;
    }

    /**
     * @api
     */
    public function setOwnerInfo($ownerInfo): self
    {
        $this->ownerInfo = $ownerInfo;

        return $this;
    }

    /**
     * @api
     */
    public function getOwnerInfo(): ?string
    {
        return $this->ownerInfo;
    }
}
