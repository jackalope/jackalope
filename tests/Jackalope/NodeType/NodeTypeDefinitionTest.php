<?php

namespace Jackalope\NodeType;

use Jackalope\TestCase;

class NodeTypeDefinitionTest extends TestCase
{
    /**
     * @expectedException   \InvalidArgumentException
     */
    public function testCtorInvalidNodeTypeDefinition()
    {
        $this->getNodeTypeManager()->createNodeTypeTemplate(new \stdclass);
    }

    public function testCreateFromArray()
    {
        $factory = $this->getMock('Jackalope\Factory');
        $nodeTypeManager = $this->getMock('Jackalope\NodeType\NodeTypeManager', array(), array(), '', false);
        $typeDef = new NodeTypeDefinition($factory, $nodeTypeManager, array(
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
        $nodeTypeManager = $this->getMock('Jackalope\NodeType\NodeTypeManager', array(), array(), '', false);
        $typeDef = new NodeTypeDefinition($factory, $nodeTypeManager, array(
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

}
