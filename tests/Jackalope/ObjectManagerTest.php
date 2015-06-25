<?php

namespace Jackalope;

class ObjectManagerTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $om;

    public function setUp()
    {
        $factory = new Factory;
        $observationManager = $this->getMock('PHPCR\Observation\ObservationManagerInterface');
        $this->om = new ObjectManager($factory, $this->getTransportStub(), $this->getSessionMock(), $observationManager);
    }

    public function testGetNodeByPath()
    {

        $path = '/jcr:root';
        $node = $this->om->getNodeByPath($path);
        $this->assertInstanceOf('Jackalope\Node', $node);
        $children = $node->getNodes();
        $this->assertInstanceOf('Iterator', $children);
        $this->assertCount(2, $children);
        $this->assertSame($node, $this->om->getNodeByPath($path));
    }

    public function testGetNodeTypes()
    {
        $nodetypes = $this->om->getNodeTypes();
        $this->assertInstanceOf('DOMDocument', $nodetypes);
        $nodetypes = $this->om->getNodeTypes(array('nt:folder', 'nt:file'));
        $this->assertInstanceOf('DOMDocument', $nodetypes);
    }
}
