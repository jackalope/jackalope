<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\ComparisonInterface;
use PHPCR\Query\QOM\StaticOperandInterface;
use PHPCR\Query\QOM\DynamicOperandInterface;

// inherit all doc
/**
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
     * @param string $operator
     * @param StaticOperandInterface $operand2
     */
    public function __construct(DynamicOperandInterface $operand1, $operator, StaticOperandInterface $operand2)
    {
        $this->operand1 = $operand1;
        $this->operator = $operator;
        $this->operand2 = $operand2;
    }

    // inherit all doc
    /**
     * @api
     */
    function getConstraint1()
    {
        return $this->constraint1;
    }

    // inherit all doc
    /**
     * @api
     */
    function getOperand1()
    {
        return $this->operand1;
    }

    // inherit all doc
    /**
     * @api
     */
    function getOperator()
    {
        return $this->operator;
    }

    // inherit all doc
    /**
     * @api
     */
    function getOperand2()
    {
        return $this->operand2;
    }
}
