<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\ChildNodeInterface;

/**
 * Tests whether the selector node is a child of a node reachable by absolute
 * path path.
 *
 * A node-tuple satisfies the constraint only if
 *  selectorNode.getParent().isSame(session.getNode(path))
 * would return true, where selectorNode is the node for the specified selector.
 *
 * @api
 */
class ChildNodeConstraint implements ChildNodeInterface
{
    /**
     * @var string
     */
    protected $selectorName;

    /**
     * @var string
     */
    protected $parentPath;

    public function __construct($path, $selectorName = null)
    {
        $this->parentPath = $path;
        $this->selectorName = $selectorName;
    }

    /**
     * {@inheritdoc}
     */
    function getSelectorName()
    {
        return $this->selectorName;
    }

    /**
     * {@inheritdoc}
     */
    function getParentPath()
    {
        return $this->parentPath;
    }
}
