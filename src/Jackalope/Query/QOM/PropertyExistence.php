<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\PropertyExistenceInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
class PropertyExistence implements PropertyExistenceInterface
{
    /**
     * @var string
     */
    protected $selectorName;

    /**
     * @var string
     */
    protected $propertyName;

    /**
     * Constructor
     *
     * @param string $selectorName
     * @param string $propertyName
     */
    public function __construct($selectorName, $propertyName)
    {
        if (null === $selectorName) {
            throw new \InvalidArgumentException('Required argument selectorName may not be null.');
        }
        $this->selectorName = $selectorName;
        $this->propertyName = $propertyName;
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
    public function getPropertyName()
    {
        return $this->propertyName;
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
