<?php
namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\LiteralInterface;

/**
 * Evaluates to a literal value.
 *
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

    /**
     * Gets the value of the literal.
     *
     * @return string the literal value; non-null
     * @api
     */
    function getLiteralValue()
    {
        return $this->literalValue;
    }
}
