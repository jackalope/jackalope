<?php
namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\DescendantNodeJoinConditionInterface;

/**
 * Tests whether the descendantSelector node is a descendant of the
 * ancestorSelector node. A node-tuple satisfies the constraint only if:
 *
 *   descendantSelectorNode.getAncestor(n).isSame(ancestorSelectorNode) && descendantSelectorNode.getDepth() > n
 *
 * would return true some some non-negative integer n, where descendantSelectorNode
 * is the node for descendantSelector and ancestorSelectorNode is the node for
 * ancestorSelector.
 *
 * @api
 */
class DescendantNodeJoinCondition implements DescendantNodeJoinConditionInterface
{
    /**
     * @var string
     */
    protected $descendantSelectorName;

    /**
     * @var string
     */
    protected $ancestorSelectorNode;

    /**
     * Constructor
     *
     * @param string $descendantSelectorName
     * @param string $ancestorSelectorName
     */
    public function __construct($descendantSelectorName, $ancestorSelectorName)
    {
        $this->ancestorSelectorNode = $ancestorSelectorName;
        $this->descendantSelectorName = $descendantSelectorName;
    }

    /**
     * Gets the name of the descendant selector.
     *
     * @return string the selector name; non-null
     * @api
     */
    function getDescendantSelectorName()
    {
        return $this->descendantSelectorName;
    }

    /**
     * Gets the name of the ancestor selector.
     *
     * @return string the selector name; non-null
     * @api
     */
    function getAncestorSelectorName()
    {
        return $this->ancestorSelectorNode;
    }
}
