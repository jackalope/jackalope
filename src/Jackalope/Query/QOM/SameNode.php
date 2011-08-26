<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\SameNodeInterface;

// inherit all doc
/**
 * @api
 */
class SameNode implements SameNodeInterface
{
    /**
     * @var string
     */
    protected $selectorName;

    /**
     * @var string
     */
    protected $path;

    /**
     * Constructor
     *
     * @param string $selectorName
     * @param string $path
     */
    public function __construct($selectorName, $path)
    {
        $this->selectorName = $selectorName;
        $this->path = $path;
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
    function getPath()
    {
        return $this->path;
    }
}
