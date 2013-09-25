<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\SelectorInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
class Selector implements SelectorInterface
{
    /**
     * @var string
     */
    protected $nodeTypeName;

    /**
     * @var string
     */
    protected $selectorName;

    /**
     * Constructor
     *
     * @param string $nodeTypeName
     * @param string $selectorName
     */
    public function __construct($selectorName, $nodeTypeName)
    {
        if (null === $selectorName) {
            throw new \InvalidArgumentException('Required argument selectorName may not be null.');
        }
        $this->selectorName = $selectorName;
        $this->nodeTypeName = $nodeTypeName;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getNodeTypeName()
    {
        return $this->nodeTypeName;
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
