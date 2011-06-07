<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\BindVariableValueInterface;

/**
 * Evaluates to the value of a bind variable.
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
     * Gets the name of the bind variable.
     *
     * @return string the bind variable name; non-null
     * @api
     */
    function getBindVariableName()
    {
        return $this->bindVariableName;
    }
}
