<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\FullTextSearchInterface;
use PHPCR\Query\QOM\StaticOperandInterface;
/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
class FullTextSearchConstraint implements FullTextSearchInterface
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
     * @var \PHPCR\Query\QOM\StaticOperandInterface
     */
    protected $searchExpression;

    /**
     * Create a new full text search constraint
     *
     * @param string $selectorName
     * @param string $propertyName
     * @param string $fullTextSearchExpression
     */
    public function __construct($selectorName, $propertyName, $fullTextSearchExpression)
    {
        if (null === $selectorName) {
            throw new \InvalidArgumentException('Required argument selectorName may not be null.');
        }
        $this->propertyName = $propertyName;
        $this->searchExpression = $fullTextSearchExpression;
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
    public function getPropertyName()
    {
        return $this->propertyName;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getFullTextSearchExpression()
    {
        return $this->searchExpression;
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
