<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\DynamicOperandInterface;
use PHPCR\Query\QOM\OrderingInterface;

/**
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
final class Ordering implements OrderingInterface
{
    private DynamicOperandInterface $operand;
    private ?string $order;

    public function __construct(DynamicOperandInterface $operand, string $order = null)
    {
        $this->operand = $operand;
        $this->order = $order;
    }

    /**
     * @api
     */
    public function getOperand(): DynamicOperandInterface
    {
        return $this->operand;
    }

    /**
     * @api
     */
    public function getOrder(): ?string
    {
        return $this->order;
    }
}
