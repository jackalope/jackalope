<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\ColumnInterface;

/**
 * {@inheritDoc}
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

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function getColumnName()
    {
        return $this->columnName;
    }
}
