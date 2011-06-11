<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\PropertyValueInterface;

/**
 * Evaluates to the value (or values, if multi-valued) of a property.
 *
 * If, for a node-tuple, the selector node does not have a property named property,
 * the operand evaluates to null.
 *
 * The query is invalid if:
 *
 * - selector is not the name of a selector in the query
 * - property is not a syntactically valid JCR name
 *
 * @api
 */
class PropertyValue implements PropertyValueInterface
{
    /**
     * @var string
     */
    protected $selectorName;

    /**
     * @var string
     */
    protected $propertyName;

    /**
     * Constructor
     *
     * @param string $selectorName
     * @param string $propertyName 
     */
    public function __construct($selectorName, $propertyName)
    {
        $this->selectorName = $selectorName;
        $this->propertyName = $propertyName;
    }

    /**
     * Gets the name of the selector against which to evaluate this operand.
     *
     * @return string the selector name; non-null
     * @api
     */
    function getSelectorName()
    {
        return $this->selectorName;
    }

    /**
     * Gets the name of the property.
     *
     * @return string the property name; non-null
     * @api
     */
    function getPropertyName()
    {
        return $this->propertyName;
    }
}
