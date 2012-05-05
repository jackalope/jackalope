<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\NodeNameInterface;

/**
 * {@inheritDoc}
 *
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

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getSelectorName()
    {
        return $this->selectorName;
    }
}
