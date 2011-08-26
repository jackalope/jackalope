<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\ChildNodeJoinConditionInterface;

// inherit all doc
/**
 * @api
 */
class ChildNodeJoinCondition implements ChildNodeJoinConditionInterface
{
    /**
     * @var string
     */
    protected $childNodeSelectorName;

    /**
     * @var string
     */
    protected $parentSelectorName;

    /**
     * Constructor
     *
     * @param string $childSelectorName
     * @param string $parentSelectorName
     */
    public function __construct($childSelectorName, $parentSelectorName)
    {
        $this->childNodeSelectorName = $childSelectorName;
        $this->parentSelectorName = $parentSelectorName;
    }

    // inherit all doc
    /**
     * @api
     */
    function getChildSelectorName()
    {
        return $this->childNodeSelectorName;
    }

    // inherit all doc
    /**
     * @api
     */
    function getParentSelectorName()
    {
        return $this->parentSelectorName;
    }
}
