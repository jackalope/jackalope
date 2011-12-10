<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\SameNodeJoinConditionInterface;

/**
 * {@inheritDoc}
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
        $this->selector1Name = $selector1Name;
        $this->selector2Name = $selector2Name;
        $this->selector2Path = $selector2Path;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function getSelector1Name()
    {
        return $this->selector1Name;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function getSelector2Name()
    {
        return $this->selector2Name;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function getSelector2Path()
    {
        return $this->selector2Path;
    }
}
