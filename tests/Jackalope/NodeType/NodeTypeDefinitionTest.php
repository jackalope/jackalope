<?php

namespace Jackalope\NodeType;

use Jackalope\TestCase;
use PHPCR\PropertyType;

class NodeTypeDefinitionTest extends TestCase
{
    /**
     * @expectedException   \InvalidArgumentException
     */
    public function testInvalidNodeTypeDefinition()
    {
        $this->getNodeTypeManager()->createNodeTypeTemplate(new \stdclass);
    }

    public function testCreateFromArray()
    {
        $factory = $this->getMock('Jackalope\Factory');
        $typeDef = new NodeTypeDefinition($factory, $this->getNodeTypeManagerMock(), array(
            'name'                      => 'test',
            'isAbstract'                => true,
            'isMixin'                   => true,
            'isQueryable'               => true,
            'hasOrderableChildNodes'    => true,
            'primaryItemName'           => 'foo',
            'supertypes'                => array(),
            'declaredPropertyDefinitions' => array(),
            'declaredNodeDefinitions'   => array(),
        ));

        $this->assertEquals('test', $typeDef->getName());
        $this->assertTrue($typeDef->isAbstract());
        $this->assertTrue($typeDef->isMixin());
        $this->assertTrue($typeDef->isQueryable());
        $this->assertTrue($typeDef->hasOrderableChildNodes());
        $this->assertEquals('foo', $typeDef->getPrimaryItemName());
        $this->assertEquals(array(), $typeDef->getDeclaredSupertypeNames(), "Supertypes should be empty");
    }

    public function testCreateFromArrayFalse()
    {
        $factory = $this->getMock('Jackalope\Factory');
        $typeDef = new NodeTypeDefinition($factory, $this->getNodeTypeManagerMock(), array(
            'name'                      => 'test',
            'isAbstract'                => false,
            'isMixin'                   => false,
            'isQueryable'               => false,
            'hasOrderableChildNodes'    => false,
            'primaryItemName'           => 'foo',
            'supertypes'                => array(),
            'declaredPropertyDefinitions' => array(),
            'declaredNodeDefinitions'   => array(),
        ));

        $this->assertFalse($typeDef->isAbstract());
        $this->assertFalse($typeDef->isMixin());
        $this->assertFalse($typeDef->isQueryable());
        $this->assertFalse($typeDef->hasOrderableChildNodes());
    }

    public function testCreateFromXml()
    {
        $factory = new \Jackalope\Factory();
        $dom = new \DOMDocument();
        $dom->loadXML('<?xml version="1.0" encoding="UTF-8"?>
    <nodeType hasOrderableChildNodes="false" isAbstract="false" isMixin="true" isQueryable="true" name="mix:created">
        <propertyDefinition autoCreated="true" declaringNodeType="mix:created" fullTextSearchable="true" mandatory="false" multiple="false" name="jcr:createdBy" onParentVersion="COPY" protected="true" queryOrderable="true" requiredType="String">
            <valueConstraints/>
            <availableQueryOperators>
                <availableQueryOperator>jcr.operator.equal.to</availableQueryOperator>
                <availableQueryOperator>jcr.operator.not.equal.to</availableQueryOperator>
                <availableQueryOperator>jcr.operator.greater.than</availableQueryOperator>
                <availableQueryOperator>jcr.operator.greater.than.or.equal.to</availableQueryOperator>
                <availableQueryOperator>jcr.operator.less.than</availableQueryOperator>
                <availableQueryOperator>jcr.operator.less.than.or.equal.to</availableQueryOperator>
                <availableQueryOperator>jcr.operator.like</availableQueryOperator>
            </availableQueryOperators>
        </propertyDefinition>
        <propertyDefinition autoCreated="true" declaringNodeType="mix:created" fullTextSearchable="true" mandatory="false" multiple="false" name="jcr:created" onParentVersion="COPY" protected="true" queryOrderable="true" requiredType="Date">
            <valueConstraints/>
            <availableQueryOperators>
                <availableQueryOperator>jcr.operator.equal.to</availableQueryOperator>
                <availableQueryOperator>jcr.operator.not.equal.to</availableQueryOperator>
                <availableQueryOperator>jcr.operator.greater.than</availableQueryOperator>
                <availableQueryOperator>jcr.operator.greater.than.or.equal.to</availableQueryOperator>
                <availableQueryOperator>jcr.operator.less.than</availableQueryOperator>
                <availableQueryOperator>jcr.operator.less.than.or.equal.to</availableQueryOperator>
                <availableQueryOperator>jcr.operator.like</availableQueryOperator>
            </availableQueryOperators>
        </propertyDefinition>
    </nodeType>');

        $nodeTypeNode = $dom->childNodes->item(0);
        $this->assertEquals('nodeType', $nodeTypeNode->nodeName);

        $typeDef = new NodeTypeDefinition($factory, $this->getNodeTypeManagerMock(), $nodeTypeNode);
        $this->assertEquals('mix:created', $typeDef->getName());
        $this->assertFalse($typeDef->hasOrderableChildNodes());
        $this->assertFalse($typeDef->isAbstract());
        $this->assertTrue($typeDef->isMixin());
        $this->assertTrue($typeDef->isQueryable());

        // Property assertions
        $propertyDefinitions = $typeDef->getDeclaredPropertyDefinitions();

        $this->assertEquals('jcr:createdBy', $propertyDefinitions[0]->getName());
        $this->assertEquals(PropertyType::STRING, $propertyDefinitions[0]->getRequiredType());
        $this->assertTrue($propertyDefinitions[0]->isAutoCreated());
        $this->assertFalse($propertyDefinitions[0]->isMandatory());
        $this->assertFalse($propertyDefinitions[0]->isMultiple());
        $this->assertTrue($propertyDefinitions[0]->isFullTextSearchable());
        $this->assertTrue($propertyDefinitions[0]->isQueryOrderable());
        $this->assertEquals(array(
                'jcr.operator.equal.to',
                'jcr.operator.not.equal.to',
                'jcr.operator.greater.than',
                'jcr.operator.greater.than.or.equal.to',
                'jcr.operator.less.than',
                'jcr.operator.less.than.or.equal.to',
                'jcr.operator.like',
        ), $propertyDefinitions[0]->getAvailableQueryOperators());

        $this->assertEquals('jcr:created', $propertyDefinitions[1]->getName());
        $this->assertEquals(PropertyType::DATE, $propertyDefinitions[1]->getRequiredType());
        $this->assertTrue($propertyDefinitions[1]->isAutoCreated());
        $this->assertFalse($propertyDefinitions[1]->isMandatory());
        $this->assertFalse($propertyDefinitions[1]->isMultiple());
        $this->assertTrue($propertyDefinitions[1]->isFullTextSearchable());
        $this->assertTrue($propertyDefinitions[1]->isQueryOrderable());
        $this->assertEquals(array(
            'jcr.operator.equal.to',
            'jcr.operator.not.equal.to',
            'jcr.operator.greater.than',
            'jcr.operator.greater.than.or.equal.to',
            'jcr.operator.less.than',
            'jcr.operator.less.than.or.equal.to',
            'jcr.operator.like',
        ), $propertyDefinitions[1]->getAvailableQueryOperators());
    }
}
