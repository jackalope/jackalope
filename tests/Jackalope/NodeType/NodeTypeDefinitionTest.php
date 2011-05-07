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

        ));
    }
}
