<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\ConstraintInterface;
use PHPCR\Query\QOM\SameNodeInterface;

/**
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
final class SameNode implements SameNodeInterface
{
    private string $selectorName;
    private string $path;

    public function __construct(string $selectorName, string $path)
    {
        $this->selectorName = $selectorName;
        $this->path = $path;
    }

    /**
     * @api
     */
    public function getSelectorName(): string
    {
        return $this->selectorName;
    }

    /**
     * @api
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Gets all constraints including itself.
     *
     * @return ConstraintInterface[]
     *
     * @api
     */
    public function getConstraints(): array
    {
        return [$this];
    }
}
