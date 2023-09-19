<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\ConstraintInterface;
use PHPCR\Query\QOM\NotInterface;

/**
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
final class NotConstraint implements NotInterface
{
    private ConstraintInterface $constraint;

    public function __construct(ConstraintInterface $constraint)
    {
        $this->constraint = $constraint;
    }

    /**
     * @api
     */
    public function getConstraint(): ConstraintInterface
    {
        return $this->constraint;
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
        $constraints = $this->getConstraint()->getConstraints();
        $constraints[] = $this;

        return $constraints;
    }
}
