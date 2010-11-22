<?php

namespace Jackalope\NodeType;

use Jackalope\TestCase;

class NodeTypeTemplateTest extends TestCase
{
    /**
     * @covers Jackalope\NodeType\NodeTypeTemplate
     */
    public function testCreateNodeTypeTemplateEmpty()
    {
        $ntm = $this->getNodeTypeManager();

        $ntt = $ntm->createNodeTypeTemplate();

        // is empty as defined by doc
        $this->assertNull($ntt->getName());
        $this->assertSame(array('nt:base'), $ntt->getDeclaredSupertypeNames());
        $this->assertFalse($ntt->isAbstract());
        $this->assertFalse($ntt->isMixin());
        $this->assertFalse($ntt->hasOrderableChildNodes());
        $this->assertFalse($ntt->isQueryable());
        $this->assertNull($ntt->getPrimaryItemName());
        $this->assertNull($ntt->getDeclaredPropertyDefinitions());
        $this->assertNull($ntt->getDeclaredChildNodeDefinitions());
    }

}
