<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\AndInterface;
use PHPCR\Query\QOM\ConstraintInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
final class AndConstraint implements AndInterface
{
    private ConstraintInterface $constraint1;
    private ConstraintInterface $constraint2;

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
    public function getConstraint1(): ConstraintInterface
    {
        return $this->constraint1;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getConstraint2(): ConstraintInterface
    {
        return $this->constraint2;
    }

    /**
     * Gets all constraints including itself.
     *
     * @return ConstraintInterface[]
     *
     * @api
     */
    public function getConstraints(): array
    {
        $constraints = array_merge($this->getConstraint1()->getConstraints(), $this->getConstraint2()->getConstraints());
        $constraints[] = $this;

        return $constraints;
    }
}
