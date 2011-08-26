<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\BindVariableValueInterface;

// inherit all doc
/**
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

    // inherit all doc
    /**
     * @api
     */
    function getBindVariableName()
    {
        return $this->bindVariableName;
    }
}
