<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\JoinInterface;
use PHPCR\Query\QOM\SourceInterface;
use PHPCR\Query\QOM\JoinConditionInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
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

    /**
     * Create a new join instance
     *
     * @param SourceInterface        $left
     * @param SourceInterface        $right
     * @param string                 $joinType
     * @param JoinConditionInterface $joinCondition
     */
    public function __construct(SourceInterface $left, SourceInterface $right, $joinType, JoinConditionInterface $joinCondition)
    {
        $this->left = $left;
        $this->right = $right;
        $this->joinType = $joinType;
        $this->joinCondition = $joinCondition;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getLeft()
    {
        return $this->left;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getRight()
    {
        return $this->right;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getJoinType()
    {
        return $this->joinType;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getJoinCondition()
    {
        return $this->joinCondition;
    }
}
