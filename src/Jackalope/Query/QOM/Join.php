<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\JoinConditionInterface;
use PHPCR\Query\QOM\JoinInterface;
use PHPCR\Query\QOM\SourceInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
final class Join implements JoinInterface
{
    private SourceInterface $left;
    private SourceInterface $right;
    private string $joinType;
    private JoinConditionInterface $joinCondition;

    public function __construct(
        SourceInterface $left,
        SourceInterface $right,
        string $joinType,
        JoinConditionInterface $joinCondition
    ) {
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
    public function getLeft(): SourceInterface
    {
        return $this->left;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getRight(): SourceInterface
    {
        return $this->right;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getJoinType(): string
    {
        return $this->joinType;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getJoinCondition(): JoinConditionInterface
    {
        return $this->joinCondition;
    }
}
