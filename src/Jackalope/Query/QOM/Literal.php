<?php
namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\LiteralInterface;

// inherit all doc
/**
 * @api
 */
class Literal implements LiteralInterface
{
    /**
     * @var string
     */
    protected $literalValue;

    /**
     * Constructor
     *
     * @param string $literalValue
     */
    public function __construct($literalValue)
    {
        $this->literalValue = $literalValue;
    }

    // inherit all doc
    /**
     * @api
     */
    function getLiteralValue()
    {
        return $this->literalValue;
    }
}
