<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\NodeNameInterface;

// inherit all doc
/**
 * @api
 */
class NodeName implements NodeNameInterface
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
