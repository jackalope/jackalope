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
     * @var PrivilegeInterface[]
     */
    private $declaredAggregate;

    /**
     * @var PrivilegeInterface[]
     */
    private $aggregate = array();

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
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function isAggregate()
    {
        return count($this->declaredAggregate) > 0;
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
        if (!$this->aggregate) {
            foreach ($this->declaredAggregate as $privilege) {
                $this->aggregate[] = $privilege;
                $this->aggregate = array_merge($this->aggregate, $privilege->getAggregatePrivileges());
            }
        }

        return $this->aggregate;
    }
}
