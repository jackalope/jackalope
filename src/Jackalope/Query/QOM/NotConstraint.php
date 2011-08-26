<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\NotInterface;
use PHPCR\Query\QOM\ConstraintInterface;

// inherit all doc
/**
 * @api
 */
class NotConstraint implements NotInterface
{
    /**
     * @var \PHPCR\Query\QOM\ConstraintInterface
     */
    protected $constraint;

    /**
     * Create a new not constraint
     *
     * @param ConstraintInterface $constraint
     */
    public function __construct(ConstraintInterface $constraint)
    {
        $this->constraint = $constraint;
    }

    // inherit all doc
    /**
     * @api
     */
    function getConstraint()
    {
        return $this->constraint;
    }
}
