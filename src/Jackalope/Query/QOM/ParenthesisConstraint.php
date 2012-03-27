<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\ConstraintInterface;
use PHPCR\Query\QOM\ParenthesisInterface;

/**
 * {@inheritDoc}
 *
 * @api
 */
class ParenthesisConstraint implements ParenthesisInterface
{
    /**
     * @var \PHPCR\Query\QOM\ConstraintInterface
     */
    protected $constraint;

    /**
     * Create a new parenthesis constraint
     *
     * @param ConstraintInterface $constraint
     */
    public function __construct(ConstraintInterface $constraint)
    {
        $this->constraint = $constraint;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function getConstraint()
    {
        return $this->constraint;
    }
}
