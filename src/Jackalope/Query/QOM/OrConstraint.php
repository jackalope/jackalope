<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\OrInterface;
use PHPCR\Query\QOM\ConstraintInterface;

/**
 * Performs a logical disjunction of two other constraints.
 *
 * To satisfy the Or constraint, the node-tuple must either:
 * - satisfy constraint1 but not constraint2, or
 * - satisfy constraint2 but not constraint1, or
 * - satisfy both constraint1 and constraint2.
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
     * Gets the first constraint.
     *
     * @return \PHPCR\Query\QOM\ConstraintInterface the constraint; non-null
     * @api
     */
    function getConstraint1()
    {
        return $this->constraint1;
    }

    /**
     * Gets the second constraint.
     *
     * @return \PHPCR\Query\QOM\ConstraintInterface the constraint; non-null
     * @api
     */
    function getConstraint2()
    {
        return $this->constraint2;
    }
}
