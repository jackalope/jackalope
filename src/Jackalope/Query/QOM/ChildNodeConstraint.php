<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\ChildNodeInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
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
     * @param string $selectorName
     * @param string $parentPath   parent path the node must be child of
     */
    public function __construct($selectorName, $parentPath)
    {
        if (null === $selectorName) {
            throw new \InvalidArgumentException('Required argument selectorName may not be null.');
        }
        $this->parentPath = $parentPath;
        $this->selectorName = $selectorName;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getSelectorName()
    {
        return $this->selectorName;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getParentPath()
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
    public function getConstraints()
    {
        return array($this);
    }
}
