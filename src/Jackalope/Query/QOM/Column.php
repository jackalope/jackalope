<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\ColumnInterface;

// inherit all doc
/**
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

    // inherit all doc
    /**
     * @api
     */
    function getSelectorName()
    {
        return $this->selectorName;
    }

    // inherit all doc
    /**
     * @api
     */
    function getPropertyName()
    {
        return $this->propertyName;
    }

    // inherit all doc
    /**
     * @api
     */
    function getColumnName()
    {
        return $this->columnName;
    }
}
