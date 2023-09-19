<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\DynamicOperandInterface;
use PHPCR\Query\QOM\LowerCaseInterface;

/**
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
final class LowerCase implements LowerCaseInterface
{
    private DynamicOperandInterface $operand;

    public function __construct(DynamicOperandInterface $operand)
    {
        $this->operand = $operand;
    }

    /**
     * @api
     */
    public function getOperand(): DynamicOperandInterface
    {
        return $this->operand;
    }
}
