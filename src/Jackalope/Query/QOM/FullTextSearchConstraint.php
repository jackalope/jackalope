<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\FullTextSearchInterface;
use PHPCR\Query\QOM\StaticOperandInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
final class FullTextSearchConstraint implements FullTextSearchInterface
{
    private string $selectorName;
    private ?string $propertyName;
    private string $searchExpression;

    public function __construct(string $selectorName, ?string $propertyName, string $fullTextSearchExpression)
    {
        $this->selectorName = $selectorName;
        $this->propertyName = $propertyName;
        $this->searchExpression = $fullTextSearchExpression;
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
    public function getPropertyName(): ?string
    {
        return $this->propertyName;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getFullTextSearchExpression(): StaticOperandInterface
    {
        return new Literal($this->searchExpression);
    }

    /**
     * Gets all constraints including itself.
     *
     * @return array the constraints
     *
     * @api
     */
    public function getConstraints()
    {
        return [$this];
    }
}
