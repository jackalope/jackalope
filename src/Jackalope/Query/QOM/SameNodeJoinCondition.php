<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\SameNodeJoinConditionInterface;

/**
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
final class SameNodeJoinCondition implements SameNodeJoinConditionInterface
{
    private string $selector1Name;
    private string $selector2Name;
    private ?string $selector2Path;

    public function __construct(string $selector1Name, string $selector2Name, string $selector2Path = null)
    {
        $this->selector1Name = (string) $selector1Name;
        $this->selector2Name = (string) $selector2Name;
        $this->selector2Path = $selector2Path;
    }

    /**
     * @api
     */
    public function getSelector1Name(): string
    {
        return $this->selector1Name;
    }

    /**
     * @api
     */
    public function getSelector2Name(): string
    {
        return $this->selector2Name;
    }

    /**
     * @api
     */
    public function getSelector2Path(): ?string
    {
        return $this->selector2Path;
    }
}
