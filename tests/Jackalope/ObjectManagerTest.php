<?php

namespace Jackalope;

class ObjectManagerTest extends TestCase
{
    public function testGetNodeByPath()
    {
        $factory = new Factory;
        $path = '/jcr:root';
        $om = new ObjectManager($factory, $this->getTransportStub($path), $this->getSessionMock());
        $node = $om->getNodeByPath($path);
        $this->assertInstanceOf('Jackalope\Node', $node);
        $children = $node->getNodes();
        $this->assertInstanceOf('Iterator', $children);
        $this->assertSame(2, count($children));
        $this->assertSame($node, $om->getNodeByPath($path));
    }

    public function testGetNodeTypes()
    {
        $factory = new Factory;
        $om = new ObjectManager($factory, $this->getTransportStub('/jcr:root'), $this->getSessionMock());
        $nodetypes = $om->getNodeTypes();
        $this->assertInstanceOf('DOMDocument', $nodetypes);
        $nodetypes = $om->getNodeTypes(array('nt:folder', 'nt:file'));
        $this->assertInstanceOf('DOMDocument', $nodetypes);
    }

}

class ObjectManagerMock extends ObjectManager
{
    public function getObjectsByUuid()
    {
        return $this->objectsByUuid;
    }
}
