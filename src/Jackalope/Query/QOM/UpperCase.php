<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\UpperCaseInterface;
use PHPCR\Query\QOM\DynamicOperandInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
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
     * {@inheritDoc}
     *
     * @api
     */
    public function getOperand()
    {
        return $this->operand;
    }
}
