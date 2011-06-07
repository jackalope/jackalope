<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\NodeNameInterface;

/**
 * Evaluates to a NAME value equal to the namespace-qualified name of a node.
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
