<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\NodeNameInterface;

/**
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
final class NodeName implements NodeNameInterface
{
    private string $selectorName;

    public function __construct(string $selectorName)
    {
        $this->selectorName = $selectorName;
    }

    /**
     * @api
     */
    public function getSelectorName(): string
    {
        return $this->selectorName;
    }
}
