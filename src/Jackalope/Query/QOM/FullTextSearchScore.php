<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\FullTextSearchScoreInterface;

// inherit all doc
/**
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

    // inherit all doc
    /**
     * @api
     */
    function getSelectorName()
    {
        return $this->selectorName;
    }
}
