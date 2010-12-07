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

}
