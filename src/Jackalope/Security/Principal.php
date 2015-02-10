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

    /**
     * @var string
     */
    private $hash;

    public function __construct($name, $hash)
    {
        $this->name = $name;
        $this->hash = $hash;
    }

    /**
     * {@inheritDoc}
     */
    public function equals($object)
    {
        // TODO
    }

    /**
     * {@inheritDoc}
     */
    public function hashCode()
    {
        return $this->hash;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }
}
