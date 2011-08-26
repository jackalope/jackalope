<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\UpperCaseInterface;
use PHPCR\Query\QOM\DynamicOperandInterface;

// inherit all doc
/**
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

    // inherit all doc
    /**
     * @api
     */
    function getOperand()
    {
        return $this->operand;
    }
}
