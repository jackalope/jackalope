<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\OrderingInterface;
use PHPCR\Query\QOM\DynamicOperandInterface;

/**
 * Determines the relative order of two node-tuples by evaluating operand for
 * each.
 *
 * For a first node-tuple, nt1, for which operand evaluates to v1, and a second
 * node-tuple, nt2, for which operand evaluates to v2:
 *
 * If order is Ascending, then:
 * - if either v1 is null, v2 is null, or both v1 and v2 are null, the relative order of nt1 and nt2 is
 *   implementation determined, otherwise
 * - if v1 is a different property type than v2, the relative order of nt1 and nt2 is implementation
 *   determined, otherwise
 * - if v1 is ordered before v2, then nt1 precedes nt2, otherwise
 * - if v1 is ordered after v2, then nt2 precedes nt1, otherwise
 *   the relative order of nt1 and nt2 is implementation determined and may be arbitrary.
 *
 * Otherwise, if order is Descending, then:
 * - if either v1 is null, v2 is null, or both v1 and v2 are null, the relative order of nt1 and nt2 is
 *   implementation determined, otherwise
 * - if v1 is a different property type than v2, the relative order of nt1 and nt2 is implementation
 *   determined, otherwise
 * - if v1 is ordered before v2, then nt2 precedes nt1, otherwise
 * - if v1 is ordered after v2, then nt1 precedes nt2, otherwise
 *   the relative order of nt1 and nt2 is implementation determined and may be arbitrary.
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
     * The operand by which to order.
     *
     * @return \PHPCR\Query\QOM\DynamicOperandInterface the operand; non-null
     * @api
     */
    function getOperand()
    {
        return $this->operand;
    }

    /**
     * Gets the order.
     *
     * @return string either QueryObjectModelConstants.JCR_ORDER_ASCENDING or QueryObjectModelConstants.JCR_ORDER_DESCENDING
     * @api
     */
    function getOrder()
    {
        return $this->order;
    }
}
