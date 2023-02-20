<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\EquiJoinConditionInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
final class EquiJoinCondition implements EquiJoinConditionInterface
{
    private string $selector1Name;
    private string $property1Name;
    private string $selector2Name;
    private string $property2Name;

    public function __construct(string $selector1Name, string $property1Name, string $selector2Name, string $property2Name)
    {
        $this->selector1Name = $selector1Name;
        $this->selector2Name = $selector2Name;
        $this->property1Name = $property1Name;
        $this->property2Name = $property2Name;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getSelector1Name(): string
    {
        return $this->selector1Name;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getProperty1Name(): string
    {
        return $this->property1Name;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getSelector2Name(): string
    {
        return $this->selector2Name;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getProperty2Name(): string
    {
        return $this->property2Name;
    }
}
