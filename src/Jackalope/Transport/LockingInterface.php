<?php

namespace Jackalope\Transport;

/**
 * Defines the method needed for node locking support.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 */
interface LockingInterface extends TransportInterface
{
    function lockNode($absPath, $isDeep, $isSessionScoped, $timeoutHint, $ownerInfo);

    function isLocked($absPath);

    function unlock($absPath, $lockToken);
}