<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\NodeLocalNameInterface;

/**
 * {@inheritDoc}
 *
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
