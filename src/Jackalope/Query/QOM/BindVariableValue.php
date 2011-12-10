<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\BindVariableValueInterface;

/**
 * {@inheritDoc}
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
    function getBindVariableName()
    {
        return $this->bindVariableName;
    }
}
