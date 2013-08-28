<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\OrInterface;
use PHPCR\Query\QOM\ConstraintInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
class OrConstraint implements OrInterface
{
    /**
     * @var \PHPCR\Query\QOM\ConstraintInterface
     */
    protected $constraint1;

    /**
     * @var \PHPCR\Query\QOM\ConstraintInterface
     */
    protected $constraint2;

    /**
     * Constructor
     *
     * @param ConstraintInterface $constraint1
     * @param ConstraintInterface $constraint2
     */
    public function __construct(ConstraintInterface $constraint1, ConstraintInterface $constraint2)
    {
        $this->constraint1 = $constraint1;
        $this->constraint2 = $constraint2;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getConstraint1()
    {
        return $this->constraint1;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getConstraint2()
    {
        return $this->constraint2;
    }

    /**
     * Gets all constraints including itself
     *
     * @return array the constraints
     *
     * @api
     */
    public function getConstraints()
    {
        $constraints = array_merge($this->getConstraint1()->getConstraints(), $this->getConstraint2()->getConstraints());
        $constraints[] = $this;

        return $constraints;
    }
}
