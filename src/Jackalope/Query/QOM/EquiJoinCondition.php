<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\EquiJoinConditionInterface;

/**
 * Tests whether the value of a property in a first selector is equal to the
 * value of a property in a second selector.
 *
 * A node-tuple satisfies the constraint only if:
 *  selector1 has a property named property1, and
 *  selector2 has a property named property2, and
 *  the value of property1 equals the value of property2
 *
 * @package phpcr
 * @subpackage interfaces
 * @api
 */
class EquiJoinCondition implements EquiJoinConditionInterface
{
    /**
     * @var string
     */
    protected $selector1Name;

    /**
     * @var string
     */
    protected $property1Name;

    /**
     * @var string
     */
    protected $selector2Name;

    /**
     * @var string
     */
    protected $property2Name;

    public function __construct($selector1Name, $property1Name, $selector2Name, $property2Name)
    {
        $this->selector1Name = $selector1Name;
        $this->selector2Name = $selector2Name;
        $this->property1Name = $property1Name;
        $this->property2Name = $property2Name;
    }

    /**
     * Gets the name of the first selector.
     *
     * @return string the selector name; non-null
     * @api
     */
    function getSelector1Name()
    {
        return $this->selector1Name;
    }

    /**
     * Gets the property name in the first selector.
     *
     * @return string the property name; non-null
     * @api
     */
    function getProperty1Name()
    {
        return $this->property1Name;
    }

    /**
     * Gets the name of the second selector.
     *
     * @return string the selector name; non-null
     * @api
     */
    function getSelector2Name()
    {
        return $this->selector2Name;
    }

    /**
     * Gets the property name in the second selector.
     *
     * @return string the property name; non-null
     * @api
     */
    function getProperty2Name()
    {
        return $this->property2Name;
    }
}
