<?php

namespace Jackalope\Security;
use PHPCR\Security\PrincipalInterface;

/**
 * {@inheritDoc}
 */
class Principal implements PrincipalInterface
{
    /**
     * @var string
     */
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
