<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\NotInterface;
use PHPCR\Query\QOM\ConstraintInterface;

/**
 * Performs a logical negation of another constraint.
 *
 * To satisfy the Not constraint, the node-tuple must not satisfy constraint.
 *
 * @api
 */
class NotConstraint implements NotInterface
{
    /**
     * @var \PHPCR\Query\QOM\ConstraintInterface
     */
    protected $constraint;

    public function __construct(\PHPCR\Query\QOM\ConstraintInterface $constraint)
    {
        $this->constraint = $constraint;
    }

    /**
     * Gets the constraint negated by this Not constraint.
     *
     * @return \PHPCR\Query\QOM\ConstraintInterface the constraint; non-null
     * @api
     */
    function getConstraint()
    {
        return $this->constraint;
    }
}
