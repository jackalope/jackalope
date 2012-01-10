<?php
namespace Jackalope\Transport\DoctrineDBAL\Query;

use PHPCR\NodeType\NodeTypeManagerInterface;
use PHPCR\Query\QOM;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Converts QOM to SQL Statements for the Doctrine DBAL database backend.
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache License Version 2.0, January 2004
 */
class QOMWalker
{
    /**
     * @var NodeTypeManagerInterface
     */
    private $nodeTypeManager;

    /**
     * @var array
     */
    private $alias = array();

    /**
     * @var AbstractPlatform
     */
    private $platform;

    private $namespaces;

    public function __construct(NodeTypeManagerInterface $manager, AbstractPlatform $platform, array $namespaces = array())
    {
        $this->nodeTypeManager = $manager;
        $this->platform = $platform;
        $this->namespaces = $namespaces;
    }

    private function getTableAlias($selectorName)
    {
        if (strpos($selectorName, ".") === false) {
            return "n";
        } else {
            $selectorAlias = array_slice(explode(".", $selectorName), 0, 1);
        }

        if (!isset($this->alias[$selectorAlias])) {
            $this->alias[$selectorAlias] = "n" . count($this->alias);
        }

        return $this->alias[$selectorAlias];
    }

    public function walkQOMQuery(QOM\QueryObjectModelInterface $qom)
    {
        $sql = "SELECT ";
        $sql .= $this->walkColumns($qom->getColumns()) . " ";
        $sql .= $this->walkSource($qom->getSource());
        if ($constraint = $qom->getConstraint()) {
            $sql .= " AND " . $this->walkConstraint($constraint);
        }
        if ($orderings = $qom->getOrderings()) {
            $sql .= " " . $this->walkOrderings($orderings);
        }

        return $sql;
    }

    public function walkColumns($columns)
    {
        $sql = '';
        if ($columns) {
            foreach ($columns as $column) {
                $sql .= $this->walkColumn($column);
            }
        }

        if ('' === trim($sql)) {
            $sql = '*';
        }

        return $sql;
    }

    public function walkColumn(QOM\ColumnInterface $column)
    {
    }

    public function walkSource(QOM\SourceInterface $source)
    {
        if (!($source instanceof QOM\SelectorInterface)) {
            throw new \Jackalope\NotImplementedException("Only Selector Sources are supported.");
        }

        $sql = "FROM phpcr_nodes n ".
               "WHERE n.workspace_id = ? AND n.type IN ('" . $source->getNodeTypeName() ."'";

        $subTypes = $this->nodeTypeManager->getSubtypes($source->getNodeTypeName());
        foreach ($subTypes as $subType) {
            /* @var $subType PHPCR\NodeType\NodeTypeInterface */
            $sql .= ", '" . $subType . "'";
        }
        $sql .= ')';

        return $sql;
    }

    public function walkConstraint(QOM\ConstraintInterface $constraint)
    {
        if ($constraint instanceof QOM\AndInterface) {
            return $this->walkAndConstraint($constraint);
        } elseif ($constraint instanceof QOM\OrInterface) {
            return $this->walkOrConstraint($constraint);
        } elseif ($constraint instanceof QOM\NotInterface) {
            return $this->walkNotConstraint($constraint);
        } elseif ($constraint instanceof QOM\ComparisonInterface) {
            return $this->walkComparisonConstraint($constraint);
        } elseif ($constraint instanceof QOM\DescendantNodeInterface) {
            return $this->walkDescendantNodeConstraint($constraint);
        } elseif ($constraint instanceof QOM\ChildNodeInterface) {
            return $this->walkChildNodeConstraint($constraint);
        } elseif ($constraint instanceof QOM\PropertyExistenceInterface) {
            return $this->walkPropertyExistanceConstraint($constraint);
        } elseif ($constraint instanceof QOM\SameNodeInterface) {
            return $this->walkSameNodeConstraint($constraint);
        }

        throw new \PHPCR\Query\InvalidQueryException("Constraint " . get_class($constraint) . " not yet supported.");
    }

    public function walkSameNodeConstraint(QOM\SameNodeInterface $constraint)
    {
        return $this->getTableAlias($constraint->getSelectorName()) . ".path = '" . $constraint->getPath() . "'";
    }

    /**
     *
     * @param QOM\PropertyExistenceInterface $constraint
     */
    public function walkPropertyExistanceConstraint(QOM\PropertyExistenceInterface $constraint)
    {
        return $this->sqlXpathValueExists($this->getTableAlias($constraint->getSelectorName()), $constraint->getPropertyName());
    }

    /**
     * @param QOM\DescendantNodeInterface $constraint
     * @return string
     */
    public function walkDescendantNodeConstraint(QOM\DescendantNodeInterface $constraint)
    {
        return $this->getTableAlias($constraint->getSelectorName()) . ".path LIKE '" . $constraint->getAncestorPath() . "/%'";
    }

    public function walkChildNodeConstraint(QOM\ChildNodeInterface $constraint)
    {
        return $this->getTableAlias($constraint->getSelectorName()) . ".parent = '" . $constraint->getParentPath() . "'";
    }

    /**
     * @param QOM\AndInterface $constraint
     * @return string
     */
    public function walkAndConstraint(QOM\AndInterface $constraint)
    {
        return "(" . $this->walkConstraint($constraint->getConstraint1()) . " AND " . $this->walkConstraint($constraint->getConstraint2()) . ")";
    }

    /**
     * @param QOM\OrInterface $constraint
     * @return string
     */
    public function walkOrConstraint(QOM\OrInterface $constraint)
    {
        return "(" . $this->walkConstraint($constraint->getConstraint1()) . " OR " . $this->walkConstraint($constraint->getConstraint2()) . ")";
    }

    /**
     * @param QOM\NotInterface $constraint
     * @return string
     */
    public function walkNotConstraint(QOM\NotInterface $constraint)
    {
        return "NOT (" . $this->walkConstraint($constraint->getConstraint()) . ")";
    }

    /**
     * @param QOM\ComparisonInterface $constraint
     */
    public function walkComparisonConstraint(QOM\ComparisonInterface $constraint)
    {
        return $this->walkOperand($constraint->getOperand1()) . " " .
               $this->walkOperator($constraint->getOperator()) . " " .
               $this->walkOperand($constraint->getOperand2());
    }

    /**
     * @param string $operator
     * @return string
     */
    public function walkOperator($operator)
    {
        if ($operator == QOM\QueryObjectModelConstantsInterface::JCR_OPERATOR_EQUAL_TO) {
            return "=";
        } elseif ($operator == QOM\QueryObjectModelConstantsInterface::JCR_OPERATOR_GREATER_THAN) {
            return ">";
        } elseif ($operator == QOM\QueryObjectModelConstantsInterface::JCR_OPERATOR_GREATER_THAN_OR_EQUAL_TO) {
            return ">=";
        } elseif ($operator == QOM\QueryObjectModelConstantsInterface::JCR_OPERATOR_LESS_THAN) {
            return "<";
        } elseif ($operator == QOM\QueryObjectModelConstantsInterface::JCR_OPERATOR_LESS_THAN_OR_EQUAL_TO) {
            return "<=";
        } elseif ($operator == QOM\QueryObjectModelConstantsInterface::JCR_OPERATOR_NOT_EQUAL_TO) {
            return "!=";
        } elseif ($operator == QOM\QueryObjectModelConstantsInterface::JCR_OPERATOR_LIKE) {
            return "LIKE";
        }

        return $operator; // no-op for simplicity, not standard conform (but using the constants is a pain)
    }

    /**
     * @param QOM\OperandInterface $operand
     */
    public function walkOperand(QOM\OperandInterface $operand)
    {
        if ($operand instanceof QOM\NodeNameInterface) {
            $selector = $operand->getSelectorName();
            $alias = $this->getTableAlias($selector);

            return $alias.".local_name"; // TODO: Hm, what about the namespace?
        } elseif ($operand instanceof QOM\NodeLocalNameInterface) {
            $selector = $operand->getSelectorName();
            $alias = $this->getTableAlias($selector);

            return $alias.".local_name";
        } elseif ($operand instanceof QOM\LowerCaseInterface) {
            return $this->platform->getLowerExpression($this->walkOperand($operand->getOperand()));
        } elseif ($operand instanceof QOM\UpperCaseInterface) {
            return $this->platform->getUpperExpression($this->walkOperand($operand->getOperand()));
        } elseif ($operand instanceof QOM\LiteralInterface) {
            return "'" . trim($operand->getLiteralValue(), '"') . "'";
        } elseif ($operand instanceof QOM\PropertyValueInterface) {
            $alias = $this->getTableAlias($operand->getSelectorName());
            $property = $operand->getPropertyName();
            if ($property == "jcr:path") {
                return $alias . ".path";
            } elseif ($property == "jcr:uuid") {
                return $alias . ".identifier";
            }

            return $this->sqlXpathExtractValue($alias, $property);
        } elseif ($operand instanceof QOM\LengthInterface) {
            $alias = $this->getTableAlias($operand->getPropertyValue()->getSelectorName());
            $property = $operand->getPropertyValue()->getPropertyName();
            if ($property == "jcr:path") {
                return $alias . ".path";
            } elseif ($property == "jcr:uuid") {
                return $alias . ".identifier";
            }

            return $this->sqlXpathExtractValue($alias, $property);
        }

        throw new \PHPCR\Query\InvalidQueryException("Dynamic operand " . get_class($operand) . " not yet supported.");
    }

    public function walkOrderings(array $orderings)
    {
        $sql = "ORDER BY ";
        foreach ($orderings as $ordering) {
            $sql .= $this->walkOrdering($ordering);
        }
        return $sql;
    }

    public function walkOrdering(QOM\OrderingInterface $ordering)
    {
        return $this->walkOperand($ordering->getOperand()) . " " .
               (($ordering->getOrder() == QOM\QueryObjectModelConstantsInterface::JCR_ORDER_ASCENDING) ? "ASC" : "DESC");
    }

    /**
     * SQL to execute an XPATH expression checking if the property exist on the node with the given alias.
     *
     * @param string $alias
     * @param string $property
     * @return string
     */
    private function sqlXpathValueExists($alias, $property)
    {
        if ($this->platform instanceof \Doctrine\DBAL\Platforms\MySqlPlatform) {
            return "EXTRACTVALUE($alias.props, 'count(//sv:property[@sv:name=\"" . $property . "\"]/sv:value[1])') = 1";
        } elseif ($this->platform instanceof \Doctrine\DBAL\Platforms\PostgreSqlPlatform) {
            return "xpath_exists('//sv:property[@sv:name=\"" . $property . "\"]/sv:value[1]', CAST($alias.props AS xml), ".$this->sqlXpathPostgreSQLNamespaces().") = 't'";
        }

        throw new \Jackalope\NotImplementedException("Xpath evaluations cannot be executed with '" . $this->platform->getName() . "' yet.");
    }

    /**
     * SQL to execute an XPATH expression extracting the property value on the node with the given alias.
     *
     * @param string $alias
     * @param string $property
     * @return string
     */
    private function sqlXpathExtractValue($alias, $property)
    {
        if ($this->platform instanceof \Doctrine\DBAL\Platforms\MySqlPlatform) {
            return "EXTRACTVALUE($alias.props, '//sv:property[@sv:name=\"" . $property . "\"]/sv:value[1]')";
        } elseif ($this->platform instanceof \Doctrine\DBAL\Platforms\PostgreSqlPlatform) {
            return "(xpath('//sv:property[@sv:name=\"" . $property . "\"]/sv:value[1]/text()', CAST($alias.props AS xml), ".$this->sqlXpathPostgreSQLNamespaces()."))[1]::text";
        }

        throw new \Jackalope\NotImplementedException("Xpath evaluations cannot be executed with '" . $this->platform->getName() . "' yet.");
    }

    private function sqlXpathPostgreSQLNamespaces()
    {
        $namespaces = "ARRAY[ARRAY['sv', 'http://www.jcp.org/jcr/sv/1.0']]";
        return $namespaces;
    }
}
