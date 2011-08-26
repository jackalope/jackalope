<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\JoinInterface;
use PHPCR\Query\QOM\SourceInterface;
use PHPCR\Query\QOM\JoinConditionInterface;

// inherit all doc
/**
 * @api
 */
class Join implements JoinInterface
{
    /**
     * @var \PHPCR\Query\QOM\SourceInterface
     */
    protected $left;

    /**
     * @var \PHPCR\Query\QOM\SourceInterface
     */
    protected $right;

    /**
     * @var string
     */
    protected $joinType;

    /**
     * @var \PHPCR\Query\QOM\JoinConditionInterface
     */
    protected $joinCondition;

    /**
     * Create a new join instance
     *
     * @param SourceInterface $left
     * @param SourceInterface $right
     * @param string $joinType
     * @param JoinConditionInterface $joinCondition
     */
    public function __construct(SourceInterface $left, SourceInterface $right, $joinType, JoinConditionInterface $joinCondition)
    {
        $this->left = $left;
        $this->right = $right;
        $this->joinType = $joinType;
        $this->joinCondition = $joinCondition;
    }

    // inherit all doc
    /**
     * @api
     */
    function getLeft()
    {
        return $this->left;
    }

    // inherit all doc
    /**
     * @api
     */
    function getRight()
    {
        return $this->right;
    }

    // inherit all doc
    /**
     * @api
     */
    function getJoinType()
    {
        return $this->joinType;
    }

    // inherit all doc
    /**
     * @api
     */
    function getJoinCondition()
    {
        return $this->joinCondition;
    }
}
