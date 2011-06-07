<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\DescendantNodeInterface;

/**
 * Tests whether the selector node is a descendant of a node reachable by
 * absolute path path.
 *
 * A node-tuple satisfies the constraint only if:
 *
 *   selectorNode.getAncestor(n).isSame(session.getNode(path)) && selectorNode.getDepth() > n
 *
 * would return true for some non-negative integer n, where selectorNode is the
 * node for the specified selector.
 *
 * @api
 */
class DescendantNodeConstraint implements DescendantNodeInterface
{
    /**
     * @var string
     */
    protected $selectorName;

    /**
     * @var string
     */
    protected $ancestorPath;

    /**
     * Constructor
     *
     * @param string $path
     * @param string $selectorName 
     */
    public function __construct($path, $selectorName = null)
    {
        $this->path = $path;
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
    function getAncestorPath()
    {
        return $this->path;
    }
}
