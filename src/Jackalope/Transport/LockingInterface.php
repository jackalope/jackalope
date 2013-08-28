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
     * Lock a node
     *
     * @param string  $absPath         The absolute path of the node
     * @param boolean $isDeep          whether this is to be a deep lock or not
     * @param boolean $isSessionScoped whether this is to be a session scoped lock
     * @param int     $timeoutHint     the optional timeout value, PHP_INT_MAX for no timeout
     * @param string  $ownerInfo       optional string to identify the owner
     *
     * @return LockInterface the lock that was created
     */
    public function lockNode($absPath, $isDeep, $isSessionScoped, $timeoutHint = PHP_INT_MAX, $ownerInfo = null);

    /**
     * Return true if the node is locked and false otherwise
     *
     * @param string $absPath The absolute path of the node
     *
     * @return boolean whether the node at that path is locked.
     */
    public function isLocked($absPath);

    /**
     * Unlock a node
     *
     * @param string $absPath   The absolute path of the node
     * @param string $lockToken The lock token of the lock to remove
     */
    public function unlock($absPath, $lockToken);
}
