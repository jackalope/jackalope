<?php
namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\LiteralInterface;

/**
 * {@inheritDoc}
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
     * {@inheritDoc}
     *
     * @api
     */
    function getLiteralValue()
    {
        return $this->literalValue;
    }
}
