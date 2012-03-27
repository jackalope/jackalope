<?php

namespace Jackalope\Query\QOM;

use Jackalope\ObjectManager;
use Jackalope\FactoryInterface;

use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;

/**
 * {@inheritDoc}
 *
 * @api
 */
class QueryObjectModelFactory implements QueryObjectModelFactoryInterface
{
    /**
     * The factory to instantiate objects
     * @var FactoryInterface
     */
    protected $factory;

    /**
     * @var \Jackalope\ObjectManager
     */
    protected $objectManager;

    /**
     * Create the query object model factory - get this from the QueryManager
     *
     * @param FactoryInterface $factory the object factory
     * @param ObjectManager $objectManager only used to create the query (can
     *      be omitted if you do not want to execute the query but just use it
     *      with a parser)
     */
    public function __construct(FactoryInterface $factory, ObjectManager $objectManager = null)
    {
        $this->factory = $factory;
        $this->objectManager = $objectManager;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function createQuery(\PHPCR\Query\QOM\SourceInterface $source,
                         \PHPCR\Query\QOM\ConstraintInterface $constraint = null,
                         array $orderings,
                         array $columns
    ) {
        return $this->factory->get('Query\QOM\QueryObjectModel',
                                   array($this->objectManager, $source, $constraint, $orderings, $columns));
    }

    // TODO: should we use the factory ->get here? but this would mean all of them need to expect the factory as first parameter
    // or refactor the factory to make the first param optional.

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function selector($nodeTypeName, $selectorName = null)
    {
        return new Selector($nodeTypeName, $selectorName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function join(\PHPCR\Query\QOM\SourceInterface $left, \PHPCR\Query\QOM\SourceInterface $right,
                         $joinType, \PHPCR\Query\QOM\JoinConditionInterface $joinCondition)
    {
        return new Join($left, $right, $joinType, $joinCondition);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function equiJoinCondition($selector1Name, $property1Name, $selector2Name, $property2Name)
    {
        return new EquiJoinCondition($selector1Name, $property1Name, $selector2Name, $property2Name);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function sameNodeJoinCondition($selector1Name, $selector2Name, $selector2Path = null)
    {
        return new SameNodeJoinCondition($selector1Name, $selector2Name, $selector2Path);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function childNodeJoinCondition($childSelectorName, $parentSelectorName)
    {
        return new ChildNodeJoinCondition($childSelectorName, $parentSelectorName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function descendantNodeJoinCondition($descendantSelectorName, $ancestorSelectorName)
    {
        return new DescendantNodeJoinCondition($descendantSelectorName, $ancestorSelectorName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function andConstraint(\PHPCR\Query\QOM\ConstraintInterface $constraint1,
                         \PHPCR\Query\QOM\ConstraintInterface $constraint2)
    {
        return new AndConstraint($constraint1, $constraint2);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function orConstraint(\PHPCR\Query\QOM\ConstraintInterface $constraint1,
                        \PHPCR\Query\QOM\ConstraintInterface $constraint2)
    {
        return new OrConstraint($constraint1, $constraint2);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function notConstraint(\PHPCR\Query\QOM\ConstraintInterface $constraint)
    {
        return new NotConstraint($constraint);
    }

    
    function ParenthesisConstraint(\PHPCR\Query\QOM\ConstraintInterface $constraint)
    {
        return new ParenthesisConstraint($constraint);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function comparison(\PHPCR\Query\QOM\DynamicOperandInterface $operand1, $operator,
                               \PHPCR\Query\QOM\StaticOperandInterface $operand2)
    {
        return new ComparisonConstraint($operand1, $operator, $operand2);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function propertyExistence($propertyName, $selectorName = null)
    {
        return new PropertyExistence($selectorName, $propertyName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function fullTextSearch($propertyName, $fullTextSearchExpression, $selectorName = null)
    {
        return new FullTextSearchConstraint($propertyName, $fullTextSearchExpression, $selectorName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function sameNode($path, $selectorName = null)
    {
        return new SameNode($selectorName, $path);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function childNode($path, $selectorName = null)
    {
        return new ChildNodeConstraint($path, $selectorName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function descendantNode($path, $selectorName = null)
    {
        return new DescendantNodeConstraint($path, $selectorName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function propertyValue($propertyName, $selectorName = null)
    {
        return new PropertyValue($selectorName, $propertyName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function length(\PHPCR\Query\QOM\PropertyValueInterface $propertyValue)
    {
        return new Length($propertyValue);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function nodeName($selectorName = null)
    {
        return new NodeName($selectorName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function nodeLocalName($selectorName = null)
    {
        return new NodeLocalName($selectorName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function fullTextSearchScore($selectorName = null)
    {
        return new FullTextSearchScore($selectorName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function lowerCase(\PHPCR\Query\QOM\DynamicOperandInterface $operand)
    {
        return new LowerCase($operand);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function upperCase(\PHPCR\Query\QOM\DynamicOperandInterface $operand)
    {
        return new UpperCase($operand);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function bindVariable($bindVariableName)
    {
        return new BindVariableValue($bindVariableName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function literal($literalValue)
    {
        return new Literal($literalValue);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function ascending(\PHPCR\Query\QOM\DynamicOperandInterface $operand)
    {
        return new Ordering($operand, \PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JCR_ORDER_ASCENDING);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function descending(\PHPCR\Query\QOM\DynamicOperandInterface $operand)
    {
        return new Ordering($operand, \PHPCR\Query\QOM\QueryObjectModelConstantsInterface::JCR_ORDER_DESCENDING);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function column($propertyName, $columnName = null, $selectorName = null)
    {
        return new Column($propertyName, $columnName, $selectorName);
    }
}
