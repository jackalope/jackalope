<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\DescendantNodeJoinConditionInterface;

/**
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
final class DescendantNodeJoinCondition implements DescendantNodeJoinConditionInterface
{
    private string $descendantSelectorName;
    private string $ancestorSelectorNode;

    public function __construct(string $descendantSelectorName, string $ancestorSelectorName)
    {
        $this->ancestorSelectorNode = $ancestorSelectorName;
        $this->descendantSelectorName = $descendantSelectorName;
    }

    /**
     * @api
     */
    public function getDescendantSelectorName(): string
    {
        return $this->descendantSelectorName;
    }

    /**
     * @api
     */
    public function getAncestorSelectorName(): string
    {
        return $this->ancestorSelectorNode;
    }
}
