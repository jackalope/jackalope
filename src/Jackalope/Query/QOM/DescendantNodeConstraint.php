<?php

namespace Jackalope\Query\QOM;

use InvalidArgumentException;
use PHPCR\Query\QOM\DescendantNodeInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
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

    protected $path;

    /**
     * Constructor
     *
     * @param string $selectorName
     * @param string $path
     *
     * @throws InvalidArgumentException
     */
    public function __construct($selectorName, $path)
    {
        if (null === $selectorName) {
            throw new InvalidArgumentException('Required argument selectorName may not be null.');
        }
        $this->selectorName = $selectorName;
        $this->path = $path;
    }

    /**
     * {@inheritDoc}
     *
     * @return string the selector name
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
     * @return string the path
     *
     * @api
     */
    public function getAncestorPath()
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
        return [$this];
    }
}
