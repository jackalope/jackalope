<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\FullTextSearchInterface;

// inherit all doc
/**
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
     * @param \PHPCR\Query\QOM\StaticOperandInterface $fullTextSearchExpression
     * @param string $selectorName
     */
    public function __construct($propertyName, $fullTextSearchExpression, $selectorName = null)
    {
        $this->propertyName = $propertyName;
        $this->searchExpression = $fullTextSearchExpression;
        $this->selectorName = $selectorName;
    }

    // inherit all doc
    /**
     * @api
     */
    function getSelectorName()
    {
        return $this->selectorName;
    }

    // inherit all doc
    /**
     * @api
     */
    function getPropertyName()
    {
        return $this->propertyName;
    }

    // inherit all doc
    /**
     * @api
     */
    function getFullTextSearchExpression()
    {
        return $this->searchExpression;
    }
}
