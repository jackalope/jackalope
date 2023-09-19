<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\ChildNodeJoinConditionInterface;

/**
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
     * @api
     */
    public function getChildSelectorName(): string
    {
        return $this->childNodeSelectorName;
    }

    /**
     * @api
     */
    public function getParentSelectorName(): string
    {
        return $this->parentSelectorName;
    }
}
