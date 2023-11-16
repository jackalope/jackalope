<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\OrderingInterface;
use PHPCR\Query\QOM\DynamicOperandInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
class Ordering implements OrderingInterface
{
    /**
     * @var DynamicOperandInterface
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
     * @param string                  $order
     */
    public function __construct(DynamicOperandInterface $operand, $order = null)
    {
        $this->operand = $operand;
        $this->order = $order;
    }

    /**
     * {@inheritDoc}
     *
     * @return DynamicOperandInterface the operand
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
     * @return string either QueryObjectModelConstants.JCR_ORDER_ASCENDING or
     *                QueryObjectModelConstants.JCR_ORDER_DESCENDING
     *
     * @api
     */
    public function getOrder()
    {
        return $this->order;
    }
}
