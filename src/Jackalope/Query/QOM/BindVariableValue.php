<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\BindVariableValueInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
class BindVariableValue implements BindVariableValueInterface
{
    /**
     * @var string
     */
    protected $bindVariableName;

    /**
     * Constructor
     *
     * @param string $bindVariableName
     */
    public function __construct($bindVariableName)
    {
        $this->bindVariableName = $bindVariableName;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getBindVariableName()
    {
        return $this->bindVariableName;
    }
}
