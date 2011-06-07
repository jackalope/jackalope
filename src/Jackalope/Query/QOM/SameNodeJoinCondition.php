<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\SameNodeJoinConditionInterface;

/**
 * Tests whether two nodes are "the same" according to the isSame method of
 * javax.jcr.Item.
 *
 * If selector2Path is omitted:
 *  Tests whether the selector1 node is the same as the selector2 node.
 *  A node-tuple satisfies the constraint only if:
 *   selector1Node.isSame(selector2Node)
 *  would return true, where selector1Node is the node for selector1 and
 *  selector2Node is the node for selector2.
 *
 * Otherwise, if selector2Path is specified:
 *  Tests whether the selector1 node is the same as a node identified by
 *  relative path from the selector2 node. A node-tuple satisfies the constraint
 *  only if:
 *   selector1Node.isSame(selector2Node.getNode(selector2Path))
 *  would return true, where selector1Node is the node for selector1 and
 *  selector2Node is the node for selector2.
 *
 * @api
 */
class SameNodeJoinCondition implements SameNodeJoinConditionInterface
{
    /**
     * @var string
     */
    protected $selector1Name;

    /**
     * @var string
     */
    protected $selector2Name;

    /**
     * @var string
     */
    protected $selector2Path;

    /**
     * Constructor
     *
     * @param string $selector1Name
     * @param string $selector2Name
     * @param string $selector2Path 
     */
    public function __construct($selector1Name, $selector2Name, $selector2Path = null)
    {
        $this->selector1Name = $selector1Name;
        $this->selector2Name = $selector2Name;
        $this->selector2Path = $selector2Path;
    }

    /**
     * Gets the name of the first selector.
     *
     * @return string the selector name; non-null
     * @api
     */
    function getSelector1Name()
    {
        return $this->selector1Name;
    }

    /**
     * Gets the name of the second selector.
     *
     * @return string the selector name; non-null
     * @api
     */
    function getSelector2Name()
    {
        return $this->selector2Name;
    }

    /**
     * Gets the path relative to the second selector.
     *
     * @return string the relative path, or null for none
     * @api
     */
    function getSelector2Path()
    {
        return $this->selector2Path;
    }
}
