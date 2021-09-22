<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\ColumnInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
final class Column implements ColumnInterface
{
    private string $selectorName;
    private ?string $propertyName;
    private ?string $columnName;

    public function __construct(string $selectorName, ?string $propertyName, ?string $columnName = null)
    {
        if ((null === $propertyName) !== (null === $columnName)) {
            throw new \InvalidArgumentException('Either both propertyName and columnName must be both null, or both non-null.');
        }

        $this->selectorName = $selectorName;
        $this->propertyName = $propertyName;
        $this->columnName = $columnName;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getSelectorName(): string
    {
        return $this->selectorName;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getPropertyName(): ?string
    {
        return $this->propertyName;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getColumnName(): ?string
    {
        return $this->columnName;
    }
}
