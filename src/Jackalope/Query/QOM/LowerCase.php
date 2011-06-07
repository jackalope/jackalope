<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\LowerCaseInterface;
use PHPCR\Query\QOM\DynamicOperandInterface;

/**
 * Evaluates to the lower-case string value (or values, if multi-valued) of
 * operand.
 *
 * If operand does not evaluate to a string value, its value is first converted
 * to a string.
 *
 * If operand evaluates to null, the LowerCase operand also evaluates to null.
 *
 * @api
 */
class LowerCase implements LowerCaseInterface
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
     * Gets the operand whose value is converted to a lower-case string.
     *
     * @return \PHPCR\Query\QOM\DynamicOperandInterface the operand; non-null
     * @api
     */
    function getOperand()
    {
        return $this->operand;
    }
}
