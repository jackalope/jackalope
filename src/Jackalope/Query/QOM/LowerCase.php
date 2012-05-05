<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\LowerCaseInterface;
use PHPCR\Query\QOM\DynamicOperandInterface;

/**
 * {@inheritDoc}
 *
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

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getOperand()
    {
        return $this->operand;
    }
}
