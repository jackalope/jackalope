<?php
namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\LengthInterface;
use PHPCR\Query\QOM\PropertyValueInterface;

/**
 * {@inheritDoc}
 *
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

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getPropertyValue()
    {
        return $this->propertyValue;
    }
}
