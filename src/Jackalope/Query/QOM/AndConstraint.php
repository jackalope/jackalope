<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\AndInterface;
use PHPCR\Query\QOM\ConstraintInterface;

/**
 * Performs a logical conjunction of two other constraints.
 *
 * To satisfy the And constraint, a node-tuple must satisfy both constraint1 and
 * constraint2.
 *
 * @api
 */
class AndConstraint implements AndInterface
{
    /**
     * @var \PHPCR\Query\QOM\ConstraintInterface
     */
    protected $constraint1;

    /**
     * @var \PHPCR\Query\QOM\ConstraintInterface
     */
    protected $constraint2;

    public function __construct(ConstraintInterface $constraint1, ConstraintInterface $constraint2)
    {
        $this->constraint1 = $constraint1;
        $this->constraint2 = $constraint2;
    }

    /**
     * {@inheritdoc}
     */
    function getConstraint1()
    {
        return $this->constraint1;
    }

    /**
     * {@inheritdoc}
     */
    function getConstraint2()
    {
        return $this->constraint2;
    }
}
