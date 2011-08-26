<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\NodeLocalNameInterface;

// inherit all doc
/**
 * @api
 */
class NodeLocalName implements NodeLocalNameInterface
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
