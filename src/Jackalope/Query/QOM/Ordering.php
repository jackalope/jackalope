<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\OrderingInterface;
use PHPCR\Query\QOM\DynamicOperandInterface;

/**
 * {@inheritDoc}
 *
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

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getOperand()
    {
        return $this->operand;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getOrder()
    {
        return $this->order;
    }
}
