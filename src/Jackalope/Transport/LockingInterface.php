<?php

namespace Jackalope\Transport;

use PHPCR\Lock\LockInterface;

/**
 * Defines the method needed for node locking support.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
interface LockingInterface extends TransportInterface
{
    /**
     * @param string $absPath         The absolute path of the node
     * @param bool   $isDeep          whether this is to be a deep lock or not
     * @param bool   $isSessionScoped whether this is to be a session scoped lock
     * @param int    $timeoutHint     the optional timeout value, PHP_INT_MAX for no timeout
     * @param string $ownerInfo       optional string to identify the owner
     */
    public function lockNode(string $absPath, bool $isDeep, bool $isSessionScoped, int $timeoutHint = PHP_INT_MAX, string $ownerInfo = null): LockInterface;

    /**
     * Return true if the node is locked and false otherwise.
     */
    public function isLocked(string $absPath): bool;

    public function unlock(string $absPath, string $lockToken): void;
}
