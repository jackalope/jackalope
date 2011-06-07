<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\UpperCaseInterface;
use PHPCR\Query\QOM\DynamicOperandInterface;

/**
 * Evaluates to the upper-case string value (or values, if multi-valued) of
 * operand.
 *
 * If operand does not evaluate to a string value, its value is first converted
 * to a string.
 *
 * If operand evaluates to null, the UpperCase operand also evaluates to null.
 *
 * @api
 */
class UpperCase implements UpperCaseInterface
{
    /**
     * @var \PHPCR\Query\QOM\DynamicOperandInterface
     */
    protected $operand;

    public function __construct(DynamicOperandInterface $operand)
    {
        $this->operand = $operand;
    }

    /**
     * Gets the operand whose value is converted to a upper-case string.
     *
     * @return \PHPCR\Query\QOM\DynamicOperandInterface the operand; non-null
     * @api
     */
    function getOperand()
    {
        return $this->operand;
    }
}
