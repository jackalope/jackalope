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
class EquiJoinCondition implements EquiJoinConditionInterface
{
    /**
     * @var string
     */
    protected $selector1Name;

    /**
     * @var string
     */
    protected $property1Name;

    /**
     * @var string
     */
    protected $selector2Name;

    /**
     * @var string
     */
    protected $property2Name;

    /**
     * Create a new EquiJoinCondition
     *
     * @param string $selector1Name
     * @param string $property1Name
     * @param string $selector2Name
     * @param string $property2Name
     */
    public function __construct($selector1Name, $property1Name, $selector2Name, $property2Name)
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
    public function getSelector1Name()
    {
        return $this->selector1Name;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getProperty1Name()
    {
        return $this->property1Name;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getSelector2Name()
    {
        return $this->selector2Name;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getProperty2Name()
    {
        return $this->property2Name;
    }
}
