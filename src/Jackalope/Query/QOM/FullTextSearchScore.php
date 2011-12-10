<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\FullTextSearchScoreInterface;

/**
 * {@inheritDoc}
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
     * {@inheritDoc}
     *
     * @api
     */
    function getSelectorName()
    {
        return $this->selectorName;
    }
}
