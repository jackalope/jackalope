<?php

namespace Jackalope\Query\QOM;

use InvalidArgumentException;
use PHPCR\Query\QOM\ColumnInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
class Column implements ColumnInterface
{
    /**
     * @var string
     */
    protected $propertyName;

    /**
     * @var string
     */
    protected $columnName;

    /**
     * @var string
     */
    protected $selectorName;

    /**
     * Constructor
     *
     * @param string $selectorName
     * @param string $propertyName
     * @param string $columnName
     *
     * @throws InvalidArgumentException
     */
    public function __construct($selectorName, $propertyName, $columnName = null)
    {
        if (null === $selectorName) {
            throw new InvalidArgumentException('Required argument selectorName may not be null.');
        }

        if ((null === $propertyName) != (null === $columnName)) {
            throw new InvalidArgumentException('Either both propertyName and columnName must be both null, or both non-null.');
        }

        $this->selectorName = $selectorName;
        $this->propertyName = $propertyName;
        $this->columnName = $columnName;
    }

    /**
     * {@inheritDoc}
     *
     * @return string the selector name
     *
     * @api
     */
    public function getSelectorName()
    {
        return $this->selectorName;
    }

    /**
     * {@inheritDoc}
     *
     * @return string|null the property name, or null to include a column for
     *                     each single-value non-residual property of the selector's node type
     *
     * @api
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }

    /**
     * {@inheritDoc}
     *
     * @return string|null the column name; must be null if getPropertyName is
     *                     null and contain the name for this column otherwise
     *
     * @api
     */
    public function getColumnName()
    {
        return $this->columnName;
    }
}
