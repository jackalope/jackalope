<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\DynamicOperandInterface as DynamicOperandInterfaceAlias;
use PHPCR\Query\QOM\UpperCaseInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
final class UpperCase implements UpperCaseInterface
{
    private DynamicOperandInterfaceAlias $operand;

    public function __construct(DynamicOperandInterfaceAlias $operand)
    {
        $this->operand = $operand;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getOperand(): DynamicOperandInterfaceAlias
    {
        return $this->operand;
    }
}
