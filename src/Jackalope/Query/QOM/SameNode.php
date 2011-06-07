<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\SameNodeInterface;

/**
 * Tests whether the selector node is reachable by absolute path path.
 *
 * A node-tuple satisfies the constraint only if:
 *
 * - selectorNode.isSame(session.getNode(path))
 *
 * would return true, where selectorNode is the node for the specified selector.
 *
 * @api
 */
class SameNode implements SameNodeInterface
{
    /**
     * @var string
     */
    protected $selectorName;

    /**
     * @var string
     */
    protected $path;

    /**
     * Constructor
     *
     * @param string $selectorName
     * @param string $path 
     */
    public function __construct($selectorName, $path)
    {
        $this->selectorName = $selectorName;
        $this->path = $path;
    }

    /**
     * Gets the name of the selector against which to apply this constraint.
     *
     * @return string the selector name; non-null
     * @api
     */
    function getSelectorName()
    {
        return $this->selectorName;
    }

    /**
     * Gets the absolute path.
     *
     * @return string the path; non-null
     * @api
     */
    function getPath()
    {
        return $this->path;
    }
}
