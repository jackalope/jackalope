<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\PropertyExistenceInterface;

/**
 * {@inheritDoc}
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
     * {@inheritDoc}
     *
     * @api
     */
    function getSelectorName()
    {
        return $this->selectorName;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function getPropertyName()
    {
        return $this->propertyName;
    }
}
