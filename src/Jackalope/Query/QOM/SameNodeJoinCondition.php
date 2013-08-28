<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\SameNodeJoinConditionInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
class SameNodeJoinCondition implements SameNodeJoinConditionInterface
{
    /**
     * @var string
     */
    protected $selector1Name;

    /**
     * @var string
     */
    protected $selector2Name;

    /**
     * @var string
     */
    protected $selector2Path;

    /**
     * Constructor
     *
     * @param string $selector1Name
     * @param string $selector2Name
     * @param string $selector2Path
     */
    public function __construct($selector1Name, $selector2Name, $selector2Path = null)
    {
        $this->selector1Name = (string) $selector1Name;
        $this->selector2Name = (string) $selector2Name;
        $this->selector2Path = $selector2Path;
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
    public function getSelector2Name()
    {
        return $this->selector2Name;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getSelector2Path()
    {
        return $this->selector2Path;
    }
}
