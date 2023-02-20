<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\ChildNodeJoinConditionInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
class ChildNodeJoinCondition implements ChildNodeJoinConditionInterface
{
    private string $childNodeSelectorName;
    private string $parentSelectorName;

    public function __construct(string $childSelectorName, string $parentSelectorName)
    {
        $this->childNodeSelectorName = $childSelectorName;
        $this->parentSelectorName = $parentSelectorName;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getChildSelectorName(): string
    {
        return $this->childNodeSelectorName;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getParentSelectorName(): string
    {
        return $this->parentSelectorName;
    }
}
