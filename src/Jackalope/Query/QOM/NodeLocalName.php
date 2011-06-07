<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\NodeLocalNameInterface;

/**
 * Evaluates to a NAME value equal to the local (unprefixed) name of a node.
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
