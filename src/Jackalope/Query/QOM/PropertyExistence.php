<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\PropertyExistenceInterface;

/**
 * Tests the existence of a property.
 *
 * A node-tuple satisfies the constraint if the selector node has a property
 * named property.
 *
 * @api
 */
class PropertyExistence implements PropertyExistenceInterface
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
     * Gets the name of the selector against which to apply this constraint.
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
