<?php
namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\LiteralInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
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
    public function getLiteralValue()
    {
        return $this->literalValue;
    }
}
