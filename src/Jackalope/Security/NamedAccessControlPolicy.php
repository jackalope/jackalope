<?php

namespace Jackalope\Security;

use PHPCR\Security\NamedAccessControlPolicyInterface;

/**
 * {@inheritDoc}
 */
class NamedAccessControlPolicy extends AccessControlPolicy implements NamedAccessControlPolicyInterface
{
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }
}
