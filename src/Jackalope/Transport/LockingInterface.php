<?php

namespace Jackalope\Transport;

/**
 * Defines the method needed for node locking support.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 */
interface LockingInterface extends TransportInterface
{
    /**
     * Lock a node
     * @param string $absPath The absolute path of the node
     * @param boolean $isDeep
     * @param boolean $isSessionScoped
     * @param int $timeoutHint
     * @param string $ownerInfo
     * @return void
     */
    function lockNode($absPath, $isDeep, $isSessionScoped, $timeoutHint, $ownerInfo);

    /**
     * Return true if the node is locked and false otherwise
     * @param string $absPath The absolute path of the node
     * @return void
     */
    function isLocked($absPath);

    /**
     * Unlock a node
     * @param string $absPath The absolute path of the node
     * @param string $lockToken The lock token of the lock to remove
     * @return void
     */
    function unlock($absPath, $lockToken);
}
