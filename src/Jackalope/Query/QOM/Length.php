<?php
namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\LengthInterface;
use PHPCR\Query\QOM\PropertyValueInterface;

// inherit all doc
/**
 * @api
 */
class Length implements LengthInterface
{
    /**
     * @var \PHPCR\Query\QOM\PropertyValueInterface
     */
    protected $propertyValue;

    /**
     * Create a new length information
     *
     * @param PropertyValueInterface $propertyValue
     */
    public function __construct(PropertyValueInterface $propertyValue)
    {
        $this->propertyValue = $propertyValue;
    }

    // inherit all doc
    /**
     * @api
     */
    function getPropertyValue()
    {
        return $this->propertyValue;
    }
}
