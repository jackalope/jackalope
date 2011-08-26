<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\SelectorInterface;

// inherit all doc
/**
 * @api
 */
class Selector implements SelectorInterface
{
    /**
     * @var string
     */
    protected $nodeTypeName;

    /**
     * @var string
     */
    protected $selectorName;

    /**
     * Constructor
     *
     * @param string $nodeTypeName
     * @param string $selectorName
     */
    public function __construct($nodeTypeName, $selectorName = null)
    {
        $this->nodeTypeName = $nodeTypeName;
        $this->selectorName = $selectorName;
    }

    // inherit all doc
    /**
     * @api
     */
    function getNodeTypeName()
    {
        return $this->nodeTypeName;
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
