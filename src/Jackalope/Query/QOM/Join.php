<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\JoinInterface;
use PHPCR\Query\QOM\SourceInterface;
use PHPCR\Query\QOM\JoinConditionInterface;

/**
 * Performs a join between two node-tuple sources.
 *
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

    public function __construct(SourceInterface $left, SourceInterface $right, $joinType, JoinConditionInterface $joinCondition)
    {
        $this->left = $left;
        $this->right = $right;
        $this->joinType = $joinType;
        $this->joinCondition = $joinCondition;
    }

    /**
     * {@inheritdoc}
     */
    function getLeft()
    {
        return $this->left;
    }

    /**
     * {@inheritdoc}
     */
    function getRight()
    {
        return $this->right;
    }

    /**
     * {@inheritdoc}
     */
    function getJoinType()
    {
        return $this->joinType;
    }

    /**
     * {@inheritdoc}
     */
    function getJoinCondition()
    {
        return $this->joinCondition;
    }
}
