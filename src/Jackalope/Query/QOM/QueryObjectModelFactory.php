<?php

namespace Jackalope\Query\QOM;

use Jackalope\ObjectManager;
use Jackalope\FactoryInterface;

use PHPCR\Query\QOM\QueryObjectModelConstantsInterface;
use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use PHPCR\Query\QOM\SourceInterface;
use PHPCR\Query\QOM\ConstraintInterface;
use PHPCR\Query\QOM\JoinConditionInterface;
use PHPCR\Query\QOM\DynamicOperandInterface;
use PHPCR\Query\QOM\StaticOperandInterface;
use PHPCR\Query\QOM\PropertyValueInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
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
     * @param FactoryInterface $factory       the object factory
     * @param ObjectManager    $objectManager only used to create the query (can
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
    public function createQuery(SourceInterface $source, ConstraintInterface $constraint = null, array $orderings = array(), array $columns = array())
    {
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
    public function selector($selectorName, $nodeTypeName)
    {
        return new Selector($selectorName, $nodeTypeName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function join(SourceInterface $left, SourceInterface $right, $joinType, JoinConditionInterface $joinCondition)
    {
        return new Join($left, $right, $joinType, $joinCondition);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function equiJoinCondition($selector1Name, $property1Name, $selector2Name, $property2Name)
    {
        return new EquiJoinCondition($selector1Name, $property1Name, $selector2Name, $property2Name);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function sameNodeJoinCondition($selector1Name, $selector2Name, $selector2Path = null)
    {
        return new SameNodeJoinCondition($selector1Name, $selector2Name, $selector2Path);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function childNodeJoinCondition($childSelectorName, $parentSelectorName)
    {
        return new ChildNodeJoinCondition($childSelectorName, $parentSelectorName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function descendantNodeJoinCondition($descendantSelectorName, $ancestorSelectorName)
    {
        return new DescendantNodeJoinCondition($descendantSelectorName, $ancestorSelectorName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function andConstraint(ConstraintInterface $constraint1, ConstraintInterface $constraint2)
    {
        return new AndConstraint($constraint1, $constraint2);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function orConstraint(ConstraintInterface $constraint1, ConstraintInterface $constraint2)
    {
        return new OrConstraint($constraint1, $constraint2);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function notConstraint(ConstraintInterface $constraint)
    {
        return new NotConstraint($constraint);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function comparison(DynamicOperandInterface $operand1, $operator, StaticOperandInterface $operand2)
    {
        return new ComparisonConstraint($operand1, $operator, $operand2);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function propertyExistence($selectorName, $propertyName)
    {
        return new PropertyExistence($selectorName, $propertyName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function fullTextSearch($selectorName, $propertyName, $fullTextSearchExpression)
    {
        return new FullTextSearchConstraint($selectorName, $propertyName, $fullTextSearchExpression);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function sameNode($selectorName, $path)
    {
        return new SameNode($selectorName, $path);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function childNode($selectorName , $path)
    {
        return new ChildNodeConstraint($selectorName, $path);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function descendantNode($selectorName, $path)
    {
        return new DescendantNodeConstraint($selectorName, $path);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function propertyValue($selectorName, $propertyName)
    {
        return new PropertyValue($selectorName, $propertyName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function length(PropertyValueInterface $propertyValue)
    {
        return new Length($propertyValue);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function nodeName($selectorName)
    {
        return new NodeName($selectorName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function nodeLocalName($selectorName)
    {
        return new NodeLocalName($selectorName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function fullTextSearchScore($selectorName)
    {
        return new FullTextSearchScore($selectorName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function lowerCase(DynamicOperandInterface $operand)
    {
        return new LowerCase($operand);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function upperCase(DynamicOperandInterface $operand)
    {
        return new UpperCase($operand);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function bindVariable($bindVariableName)
    {
        return new BindVariableValue($bindVariableName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function literal($literalValue)
    {
        return new Literal($literalValue);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function ascending(DynamicOperandInterface $operand)
    {
        return new Ordering($operand, QueryObjectModelConstantsInterface::JCR_ORDER_ASCENDING);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function descending(DynamicOperandInterface $operand)
    {
        return new Ordering($operand, QueryObjectModelConstantsInterface::JCR_ORDER_DESCENDING);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function column($selectorName, $propertyName = null, $columnName = null)
    {
        return new Column($selectorName, $propertyName, $columnName);
    }
}
