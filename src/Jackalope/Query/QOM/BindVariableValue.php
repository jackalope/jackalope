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
final class BindVariableValue implements BindVariableValueInterface
{
    private string $bindVariableName;

    public function __construct(string $bindVariableName)
    {
        $this->bindVariableName = $bindVariableName;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getBindVariableName(): string
    {
        return $this->bindVariableName;
    }
}
