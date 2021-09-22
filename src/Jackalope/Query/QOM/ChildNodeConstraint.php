<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\ChildNodeInterface;
use PHPCR\Query\QOM\ConstraintInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
final class ChildNodeConstraint implements ChildNodeInterface
{
    private string $selectorName;
    private string $parentPath;

    public function __construct(string $selectorName, string $parentPath)
    {
        $this->parentPath = $parentPath;
        $this->selectorName = $selectorName;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getSelectorName(): string
    {
        return $this->selectorName;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getParentPath(): string
    {
        return $this->parentPath;
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
