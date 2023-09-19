<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\SelectorInterface;

/**
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
final class Selector implements SelectorInterface
{
    private string $nodeTypeName;
    private string $selectorName;

    public function __construct(string $selectorName, string $nodeTypeName)
    {
        $this->selectorName = $selectorName;
        $this->nodeTypeName = $nodeTypeName;
    }

    /**
     * @api
     */
    public function getNodeTypeName(): string
    {
        return $this->nodeTypeName;
    }

    /**
     * @api
     */
    public function getSelectorName(): string
    {
        return $this->selectorName;
    }
}
