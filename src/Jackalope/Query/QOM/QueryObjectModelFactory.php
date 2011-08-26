<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;

// inherit all doc
/**
 * @api
 */
class QueryObjectModelFactory implements QueryObjectModelFactoryInterface
{
    // inherit all doc
    /**
     * @api
     */
    function createQuery(\PHPCR\Query\QOM\SourceInterface $source, $constraint, array $orderings, array $columns)
    {
        return new QueryObjectModel($source, $constraint, $orderings, $columns);
    }

    // inherit all doc
    /**
     * @api
     */
    function selector($nodeTypeName, $selectorName = null)
    {
        return new Selector($nodeTypeName, $selectorName);
    }

    // inherit all doc
    /**
     * @api
     */
    function join(\PHPCR\Query\QOM\SourceInterface $left, \PHPCR\Query\QOM\SourceInterface $right,
                         $joinType, \PHPCR\Query\QOM\JoinConditionInterface $joinCondition)
    {
        return new Join($left, $right, $joinType, $joinCondition);
    }

    // inherit all doc
    /**
     * @api
     */
    function equiJoinCondition($selector1Name, $property1Name, $selector2Name, $property2Name)
    {
        return new EquiJoinCondition($selector1Name, $property1Name, $selector2Name, $property2Name);
    }

    // inherit all doc
    /**
     * @api
     */
    function sameNodeJoinCondition($selector1Name, $selector2Name, $selector2Path = null)
    {
        return new SameNodeJoinCondition($selector1Name, $selector2Name, $selector2Path);
    }

    // inherit all doc
    /**
     * @api
     */
    function childNodeJoinCondition($childSelectorName, $parentSelectorName)
    {
        return new ChildNodeJoinCondition($childSelectorName, $parentSelectorName);
    }

    // inherit all doc
    /**
     * @api
     */
    function descendantNodeJoinCondition($descendantSelectorName, $ancestorSelectorName)
    {
        return new DescendantNodeJoinCondition($descendantSelectorName, $ancestorSelectorName);
    }

    // inherit all doc
    /**
     * @api
     */
    function _and(\PHPCR\Query\QOM\ConstraintInterface $constraint1,
                         \PHPCR\Query\QOM\ConstraintInterface $constraint2)
    {
        return new AndConstraint($constraint1, $constraint2);
    }

    // inherit all doc
    /**
     * @api
     */
    function _or(\PHPCR\Query\QOM\ConstraintInterface $constraint1,
                        \PHPCR\Query\QOM\ConstraintInterface $constraint2)
    {
        return new OrConstraint($constraint1, $constraint2);
    }

    // inherit all doc
    /**
     * @api
     */
    function not(\PHPCR\Query\QOM\ConstraintInterface $constraint)
    {
        return new NotConstraint($constraint);
    }

    // inherit all doc
    /**
     * @api
     */
    function comparison(\PHPCR\Query\QOM\DynamicOperandInterface $operand1, $operator,
                               \PHPCR\Query\QOM\StaticOperandInterface $operand2)
    {
        return new ComparisonConstraint($operand1, $operator, $operand2);
    }

    // inherit all doc
    /**
     * @api
     */
    function propertyExistence($propertyName, $selectorName = null)
    {
        return new PropertyExistence($selectorName, $propertyName);
    }

    // inherit all doc
    /**
     * @api
     */
    function fullTextSearch($propertyName, $fullTextSearchExpression, $selectorName = null)
    {
        return new FullTextSearchConstraint($propertyName, $fullTextSearchExpression, $selectorName);
    }

    // inherit all doc
    /**
     * @api
     */
    function sameNode($path, $selectorName = null)
    {
        return new SameNode($selectorName, $path);
    }

    // inherit all doc
    /**
     * @api
     */
    function childNode($path, $selectorName = null)
    {
        return new ChildNodeConstraint($path, $selectorName);
    }

    // inherit all doc
    /**
     * @api
     */
    function descendantNode($path, $selectorName = null)
    {
        return new DescendantNodeConstraint($path, $selectorName);
    }

    // inherit all doc
    /**
     * @api
     */
    function propertyValue($propertyName, $selectorName = null)
    {
        return new PropertyValue($selectorName, $propertyName);
    }

    // inherit all doc
    /**
     * @api
     */
    function length(\PHPCR\Query\QOM\PropertyValueInterface $propertyValue)
    {
        return new Length($propertyValue);
    }

    // inherit all doc
    /**
     * @api
     */
    function nodeName($selectorName = null)
    {
        return new NodeName($selectorName);
    }

    // inherit all doc
    /**
     * @api
     */
    function nodeLocalName($selectorName = null)
    {
        return new NodeLocalName($selectorName);
    }

    // inherit all doc
    /**
     * @api
     */
    function fullTextSearchScore($selectorName = null)
    {
        return new FullTextSearchScore($selectorName);
    }

    // inherit all doc
    /**
     * @api
     */
    function lowerCase(\PHPCR\Query\QOM\DynamicOperandInterface $operand)
    {
        return new LowerCase($operand);
    }

    // inherit all doc
    /**
     * @api
     */
    function upperCase(\PHPCR\Query\QOM\DynamicOperandInterface $operand)
    {
        return new UpperCase($operand);
    }

    // inherit all doc
    /**
     * @api
     */
    function bindVariable($bindVariableName)
    {
        return new BindVariableValue($bindVariableName);
    }

    // inherit all doc
    /**
     * @api
     */
    function literal($literalValue)
    {
        return new Literal($literalValue);
    }

    // inherit all doc
    /**
     * @api
     */
    function ascending(\PHPCR\Query\QOM\DynamicOperandInterface $operand)
    {
        return new Ordering($operand, \PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JCR_ORDER_ASCENDING);
    }

    // inherit all doc
    /**
     * @api
     */
    function descending(\PHPCR\Query\QOM\DynamicOperandInterface $operand)
    {
        return new Ordering($operand, \PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JCR_ORDER_DESCENDING);
    }

    // inherit all doc
    /**
     * @api
     */
    function column($propertyName, $columnName = null, $selectorName = null)
    {
        return new Column($propertyName, $columnName, $selectorName);
    }
}
