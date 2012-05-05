<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\FullTextSearchInterface;
use PHPCR\Query\QOM\StaticOperandInterface;
/**
 * {@inheritDoc}
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
     * @param string $propertyName
     * @param string $fullTextSearchExpression
     * @param string $selectorName
     */
    public function __construct($propertyName, $fullTextSearchExpression, $selectorName = null)
    {
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
