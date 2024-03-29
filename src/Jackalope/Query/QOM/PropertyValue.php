<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\PropertyValueInterface;

/**
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
final class PropertyValue implements PropertyValueInterface
{
    private string $selectorName;
    private string $propertyName;

    public function __construct(string $selectorName, string $propertyName)
    {
        $this->selectorName = $selectorName;
        $this->propertyName = $propertyName;
    }

    /**
     * @api
     */
    public function getSelectorName(): string
    {
        return $this->selectorName;
    }

    /**
     * @api
     */
    public function getPropertyName(): string
    {
        return $this->propertyName;
    }
}
