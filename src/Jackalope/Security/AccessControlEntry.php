<?php

namespace Jackalope\Security;

use PHPCR\Security\AccessControlEntryInterface;
use PHPCR\Security\PrincipalInterface;
use PHPCR\Security\PrivilegeInterface;

/**
 * {@inheritDoc}
 */
class AccessControlEntry implements AccessControlEntryInterface
{
    /**
     * @var PrincipalInterface
     */
    private $principal;

    /**
     * @var PrivilegeInterface[]
     */
    private $privileges;

    public function __construct(PrincipalInterface $principal, array $privileges)
    {
        $this->principal = $principal;
        // TODO: validate privileges
        $this->privileges = $privileges;
    }

    /**
     * {@inheritDoc}
     */
    public function getPrincipal()
    {
        return $this->principal;
    }

    /**
     * {@inheritDoc}
     */
    public function getPrivileges()
    {
        return $this->privileges;
    }
}
