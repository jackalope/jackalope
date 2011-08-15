<?php
namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\LengthInterface;
use PHPCR\Query\QOM\PropertyValueInterface;

/**
 * Evaluates to the length (or lengths, if multi-valued) of a property.
 *
 * The length should be computed as though the getLength method of
 * \PHPCR\PropertyInterface were called.
 *
 * If propertyValue evaluates to null, the Length operand also evaluates to null.
 *
 * @api
 */
class Length implements LengthInterface
{
    /**
     * @var \PHPCR\Query\QOM\PropertyValueInterface
     */
    protected $propertyValue;

    public function __construct(PropertyValueInterface $propertyValue)
    {
        $this->propertyValue = $propertyValue;
    }

    /**
     * Gets the property value for which to compute the length.
     *
     * @return \PHPCR\Query\QOM\PropertyValueInterface the property value; non-null
     * @api
     */
    function getPropertyValue()
    {
        return $this->propertyValue;
    }
}
