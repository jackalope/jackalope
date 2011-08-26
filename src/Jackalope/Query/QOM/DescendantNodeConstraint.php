<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\DescendantNodeInterface;

// inherit all doc
/**
 * @api
 */
class DescendantNodeConstraint implements DescendantNodeInterface
{
    /**
     * @var string
     */
    protected $selectorName;

    /**
     * @var string
     */
    protected $ancestorPath;

    /**
     * Constructor
     *
     * @param string $path
     * @param string $selectorName
     */
    public function __construct($path, $selectorName = null)
    {
        $this->path = $path;
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
    function getAncestorPath()
    {
        return $this->path;
    }
}
