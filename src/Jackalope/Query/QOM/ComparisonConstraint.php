<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\ComparisonInterface;
use PHPCR\Query\QOM\DynamicOperandInterface;
use PHPCR\Query\QOM\StaticOperandInterface;

/**
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
final class ComparisonConstraint implements ComparisonInterface
{
    private DynamicOperandInterface $operand1;
    private string $operator;
    private StaticOperandInterface $operand2;

    public function __construct(DynamicOperandInterface $operand1, string $operator, StaticOperandInterface $operand2)
    {
        $this->operand1 = $operand1;
        $this->operator = $operator;
        $this->operand2 = $operand2;
    }

    /**
     * Gets all constraints including itself.
     *
     * @return ComparisonConstraint[] the constraints
     *
     * @api
     */
    public function getConstraints(): array
    {
        return [$this];
    }

    /**
     * @api
     */
    public function getOperand1(): DynamicOperandInterface
    {
        return $this->operand1;
    }

    /**
     * @api
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @api
     */
    public function getOperand2(): StaticOperandInterface
    {
        return $this->operand2;
    }
}
