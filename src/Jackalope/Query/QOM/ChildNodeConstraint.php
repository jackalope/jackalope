<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\ChildNodeInterface;

/**
 * {@inheritDoc}
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

    /**
     * Create a new child node constraint
     *
     * @param string $parentPath parent path the node must be child of
     * @param string $selectorName optionally restrict to a selector
     */
    public function __construct($parentPath, $selectorName = null)
    {
        $this->parentPath = $parentPath;
        $this->selectorName = $selectorName;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function getSelectorName()
    {
        return $this->selectorName;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function getParentPath()
    {
        return $this->parentPath;
    }

    /**
     * Gets all constraints including itself
     *
     * @return array the constraints
     *
     * @api
     */
    function getConstraints() {
        return array($this);
    }    
}
