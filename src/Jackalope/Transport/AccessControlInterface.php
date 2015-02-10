<?php

namespace Jackalope\Transport;
use PHPCR\Security\AccessControlPolicyInterface;

/**
 * Defines the method needed for access control support.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
interface AccessControlInterface extends TransportInterface
{
    public function getPolicies($path);

    public function getSupportedPrivileges($path = null);

    public function setPolicy(SetPolicyOperation $operation);

    // TODO: store
}
