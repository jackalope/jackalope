<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\ChildNodeJoinConditionInterface;

/**
 * Tests whether the childSelector node is a child of the parentSelector node. A
 * node-tuple satisfies the constraint only if:
 *  childSelectorNode.getParent().isSame(parentSelectorNode)
 * would return true, where childSelectorNode is the node for childSelector and
 * parentSelectorNode is the node for parentSelector.
 *
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

    /**
     * Gets the name of the child selector.
     *
     * @return string the selector name; non-null
     * @api
     */
    function getChildSelectorName()
    {
        return $this->childNodeSelectorName;
    }

    /**
     * Gets the name of the parent selector.
     *
     * @return string the selector name; non-null
     * @api
     */
    function getParentSelectorName()
    {
        return $this->parentSelectorName;
    }
}
