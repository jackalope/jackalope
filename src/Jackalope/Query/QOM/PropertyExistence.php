<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\ConstraintInterface;
use PHPCR\Query\QOM\PropertyExistenceInterface;

/**
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
final class PropertyExistence implements PropertyExistenceInterface
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

    /**
     * Gets all constraints including itself.
     *
     * @return ConstraintInterface[]
     *
     * @api
     */
    public function getConstraints(): array
    {
        return [$this];
    }
}
