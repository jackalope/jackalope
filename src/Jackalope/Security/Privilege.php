<?php

namespace Jackalope\Security;

use PHPCR\Security\PrivilegeInterface;

/**
 * {@inheritDoc}
 */
class Privilege implements PrivilegeInterface
{
    /**
     * @var $name
     */
    private $name;

    /**
     * @var array
     */
    private $declaredAggregate;

    public function __construct($name, $declaredAggregate = array())
    {
        $this->name = $name;
        $this->declaredAggregate = $declaredAggregate;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function isAbstract()
    {
        // TODO: how to know this?
    }

    /**
     * {@inheritDoc}
     */
    public function isAggregate()
    {
        return count($this->aggregate) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getDeclaredAggregatePrivileges()
    {
        return $this->declaredAggregate;
    }

    /**
     * {@inheritDoc}
     */
    public function getAggregatePrivileges()
    {
        return $this->aggregate;
    }
}
