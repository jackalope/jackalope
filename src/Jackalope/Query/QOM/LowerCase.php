<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\LowerCaseInterface;
use PHPCR\Query\QOM\DynamicOperandInterface;

// inherit all doc
/**
 * @api
 */
class LowerCase implements LowerCaseInterface
{
    /**
     * @var \PHPCR\Query\QOM\DynamicOperandInterface
     */
    protected $operand;

    /**
     * Create a new lower case value
     *
     * @param DynamicOperandInterface $operand
     */
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
