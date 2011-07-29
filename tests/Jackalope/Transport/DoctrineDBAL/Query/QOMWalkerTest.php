<?php

namespace Jackalope\Transport\DoctrineDBAL\Query;

use Jackalope\Transport\DoctrineDBAL\DoctrineDBALTestCase;
use Jackalope\Query\QOM\QueryObjectModelFactory;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface;

class QOMWalkerTest extends DoctrineDBALTestCase
{    
    /**
     *
     * @var QueryObjectModelFactory
     */
    private $factory;

    private $walker;

    private $nodeTypeManager;
    
    public function setUp()
    {
        parent::setUp();

        $conn = $this->getConnection();
        $this->nodeTypeManager = $this->getMock('Jackalope\NodeType\NodeTypeManager', array(), array(), '', false);
        $this->factory = new QueryObjectModelFactory;
        $this->walker = new QOMWalker($this->nodeTypeManager, $conn->getDatabasePlatform());
    }
    
    public function testDefaultQuery()
    {
        $this->nodeTypeManager->expects($this->once())->method('getSubtypes')->will($this->returnValue( array() ));

        $query = $this->factory->createQuery($this->factory->selector('nt:unstructured'), null, array(), array());
        $sql = $this->walker->walkQOMQuery($query);

        $this->assertEquals("SELECT * FROM phpcr_nodes n WHERE n.workspace_id = ? AND n.type IN ('nt:unstructured')", $sql);
    }

    public function testQueryWithPathComparisonConstraint()
    {
        $this->nodeTypeManager->expects($this->once())->method('getSubtypes')->will($this->returnValue( array() ));

        $query = $this->factory->createQuery(
            $this->factory->selector('nt:unstructured'), 
            $this->factory->comparison($this->factory->propertyValue('jcr:path'), '=', $this->factory->literal('/')),
            array(),
            array()
        );
        $sql = $this->walker->walkQOMQuery($query);

        $this->assertEquals("SELECT * FROM phpcr_nodes n WHERE n.workspace_id = ? AND n.type IN ('nt:unstructured') AND n.path = '/'", $sql);
    }

    public function testQueryWithPropertyComparisonConstraint()
    {
        $this->nodeTypeManager->expects($this->once())->method('getSubtypes')->will($this->returnValue( array() ));

        $query = $this->factory->createQuery(
            $this->factory->selector('nt:unstructured'),
            $this->factory->comparison($this->factory->propertyValue('jcr:createdBy'), '=', $this->factory->literal('beberlei')),
            array(),
            array()
        );
        $sql = $this->walker->walkQOMQuery($query);

        $this->assertEquals(
            "SELECT * FROM phpcr_nodes n WHERE n.workspace_id = ? AND n.type IN ('nt:unstructured') AND EXTRACTVALUE(n.props, '//sv:property[@sv:name=\"jcr:createdBy\"]/sv:value[1]') = 'beberlei'",
            $sql
        );
    }

    public function testQueryWithAndConstraint()
    {
        $this->nodeTypeManager->expects($this->once())->method('getSubtypes')->will($this->returnValue( array() ));

        $query = $this->factory->createQuery(
            $this->factory->selector('nt:unstructured'),
            $this->factory->_and(
                $this->factory->comparison($this->factory->propertyValue('jcr:path'), '=', $this->factory->literal('/')),
                $this->factory->comparison($this->factory->propertyValue('jcr:path'), '=', $this->factory->literal('/'))
            ),
            array(),
            array()
        );
        $sql = $this->walker->walkQOMQuery($query);

        $this->assertEquals("SELECT * FROM phpcr_nodes n WHERE n.workspace_id = ? AND n.type IN ('nt:unstructured') AND (n.path = '/' AND n.path = '/')", $sql);
    }

    public function testQueryWithOrConstraint()
    {
        $this->nodeTypeManager->expects($this->once())->method('getSubtypes')->will($this->returnValue( array() ));

        $query = $this->factory->createQuery(
            $this->factory->selector('nt:unstructured'),
            $this->factory->_or(
                $this->factory->comparison($this->factory->propertyValue('jcr:path'), '=', $this->factory->literal('/')),
                $this->factory->comparison($this->factory->propertyValue('jcr:path'), '=', $this->factory->literal('/'))
            ),
            array(),
            array()
        );
        $sql = $this->walker->walkQOMQuery($query);

        $this->assertEquals("SELECT * FROM phpcr_nodes n WHERE n.workspace_id = ? AND n.type IN ('nt:unstructured') AND (n.path = '/' OR n.path = '/')", $sql);
    }

    public function testQueryWithNotConstraint()
    {
        $this->nodeTypeManager->expects($this->once())->method('getSubtypes')->will($this->returnValue( array() ));

        $query = $this->factory->createQuery(
            $this->factory->selector('nt:unstructured'),
            $this->factory->not(
                $this->factory->comparison($this->factory->propertyValue('jcr:path'), '=', $this->factory->literal('/'))
            ),
            array(),
            array()
        );
        $sql = $this->walker->walkQOMQuery($query);

        $this->assertEquals("SELECT * FROM phpcr_nodes n WHERE n.workspace_id = ? AND n.type IN ('nt:unstructured') AND NOT (n.path = '/')", $sql);
    }

    static public function dataQueryWithOperator()
    {
        return array(
            array(QueryObjectModelConstantsInterface::JCR_OPERATOR_EQUAL_TO, "="),
            array(QueryObjectModelConstantsInterface::JCR_OPERATOR_GREATER_THAN, ">"),
            array(QueryObjectModelConstantsInterface::JCR_OPERATOR_GREATER_THAN_OR_EQUAL_TO, ">="),
            array(QueryObjectModelConstantsInterface::JCR_OPERATOR_LESS_THAN, "<"),
            array(QueryObjectModelConstantsInterface::JCR_OPERATOR_LESS_THAN_OR_EQUAL_TO, "<="),
            array(QueryObjectModelConstantsInterface::JCR_OPERATOR_NOT_EQUAL_TO, "!="),
            array(QueryObjectModelConstantsInterface::JCR_OPERATOR_LIKE,"LIKE")
        );
    }

    /**
     * @dataProvider dataQueryWithOperator
     * @param type $const
     * @param type $op
     */
    public function testQueryWithOperator($const, $op)
    {
        $this->nodeTypeManager->expects($this->once())->method('getSubtypes')->will($this->returnValue( array() ));

        $query = $this->factory->createQuery(
            $this->factory->selector('nt:unstructured'),
            $this->factory->comparison($this->factory->propertyValue('jcr:path'), $const, $this->factory->literal('/')),
            array(),
            array()
        );
        $sql = $this->walker->walkQOMQuery($query);

        $this->assertEquals("SELECT * FROM phpcr_nodes n WHERE n.workspace_id = ? AND n.type IN ('nt:unstructured') AND n.path $op '/'", $sql);
    }

    public function testQueryWithOrderings()
    {
        $this->nodeTypeManager->expects($this->once())->method('getSubtypes')->will($this->returnValue( array() ));

        $query = $this->factory->createQuery(
            $this->factory->selector('nt:unstructured'),
            null,
            array($this->factory->ascending($this->factory->propertyValue("jcr:path"))),
            array()
        );

        $sql = $this->walker->walkQOMQuery($query);

        $this->assertEquals(
            "SELECT * FROM phpcr_nodes n WHERE n.workspace_id = ? AND n.type IN ('nt:unstructured') ORDER BY n.path ASC",
            $sql
        );
    }
}