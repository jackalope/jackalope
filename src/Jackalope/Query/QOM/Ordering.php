<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\OrderingInterface;
use PHPCR\Query\QOM\DynamicOperandInterface;

// inherit all doc
/**
 * @api
 */
class Ordering implements OrderingInterface
{
    /**
     * @var \PHPCR\Query\QOM\DynamicOperandInterface
     */
    protected $operand;

    /**
     * @var string
     */
    protected $order;

    /**
     * Constructor
     *
     * @param DynamicOperandInterface $operand
     * @param string $order
     */
    public function __construct(DynamicOperandInterface $operand, $order = null)
    {
        $this->operand = $operand;
        $this->order = $order;
    }

    // inherit all doc
    /**
     * @api
     */
    function getOperand()
    {
        return $this->operand;
    }

    // inherit all doc
    /**
     * @api
     */
    function getOrder()
    {
        return $this->order;
    }
}
