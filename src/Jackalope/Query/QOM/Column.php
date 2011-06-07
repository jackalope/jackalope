<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\ColumnInterface;


/**
 * Defines a column to include in the tabular view of query results.
 *
 * If property is not specified, a column is included for each single-valued
 * non-residual property of the node type specified by the nodeType attribute of
 * selector.
 *
 * If property is specified, columnName is required and used to name the column
 * in the tabular results. If property is not specified, columnName must not be
 * specified, and the included columns will be named "selector.propertyName".
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
     * @param string $propertyName
     * @param string $columnName
     * @param string $selectorName 
     */
    public function __construct($propertyName, $columnName = null, $selectorName = null)
    {
        $this->propertyName = $propertyName;
        $this->columnName = $columnName;
        $this->selectorName = $selectorName;
    }

    /**
     * Gets the name of the selector.
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
     * @return string the property name, or null to include a column for each single-value non-residual property of the selector's node type
     * @api
     */
    function getPropertyName()
    {
        return $this->propertyName;
    }

    /**
     * Gets the column name.
     *
     * @return string the column name; must be null if getPropertyName is null and non-null otherwise
     * @api
     */
    function getColumnName()
    {
        return $this->columnName;
    }
}
