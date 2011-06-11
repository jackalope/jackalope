<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\FullTextSearchScoreInterface;

/**
 * Evaluates to a DOUBLE value equal to the full-text search score of a node.
 *
 * Full-text search score ranks a selector's nodes by their relevance to the
 * fullTextSearchExpression specified in a FullTextSearch. The values to which
 * FullTextSearchScore evaluates and the interpretation of those values are
 * implementation specific. FullTextSearchScore may evaluate to a constant value
 * in a repository that does not support full-text search scoring or has no
 * full-text indexed properties.
 *
 * @api
 */
class FullTextSearchScore implements FullTextSearchScoreInterface
{
    /**
     * @var string
     */
    protected $selectorName;

    /**
     * Constructor
     *
     * @param string $selectorName 
     */
    public function __construct($selectorName)
    {
        $this->selectorName = $selectorName;
    }

    /**
     * Gets the name of the selector against which to evaluate this operand.
     *
     * @return string the selector name; non-null
     * @api
     */
    function getSelectorName()
    {
        return $this->selectorName;
    }
}
