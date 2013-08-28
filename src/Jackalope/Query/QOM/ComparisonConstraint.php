<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\ComparisonInterface;
use PHPCR\Query\QOM\StaticOperandInterface;
use PHPCR\Query\QOM\DynamicOperandInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
class ComparisonConstraint implements ComparisonInterface
{
    /**
     * @var \PHPCR\Query\QOM\DynamicOperandInterface
     */
    protected $operand1;

    /**
     * @var string
     */
    protected $operator;

    /**
     * @var \PHPCR\Query\QOM\StaticOperandInterface
     */
    protected $operand2;

    /**
     * Create a new comparison constraint
     *
     * @param DynamicOperandInterface $operand1
     * @param string                  $operator
     * @param StaticOperandInterface  $operand2
     */
    public function __construct(DynamicOperandInterface $operand1, $operator, StaticOperandInterface $operand2)
    {
        $this->operand1 = $operand1;
        $this->operator = $operator;
        $this->operand2 = $operand2;
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
        return array($this);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getOperand1()
    {
        return $this->operand1;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getOperand2()
    {
        return $this->operand2;
    }
}
