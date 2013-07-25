<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\SameNodeInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
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
        if (null === $selectorName) {
            throw new \InvalidArgumentException('Required argument selectorName may not be null.');
        }
        $this->selectorName = $selectorName;
        $this->path = $path;
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
    public function getPath()
    {
        return $this->path;
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
