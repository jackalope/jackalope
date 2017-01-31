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
        $session = $this->getSessionMock();
        $workspace = $session->getWorkspace();

        $ntMock = $this->getNodeTypeMock(array('isNodeType' => true));
        $ntmMock = $this->getNodeTypeManagerMock(array('getNodeType' => $ntMock));

        $workspace->expects($this->any())
            ->method('getNodeTypeManager')
            ->will($this->returnValue($ntmMock));

        $this->om = new ObjectManager($factory, $this->getTransportStub(), $session);
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

    public function testRegisterUuid()
    {
        $this->om->registerUuid('1234', '/jcr:root');
        $node = $this->om->getNodeByIdentifier('1234');

        $this->assertInstanceOf('Jackalope\Node', $node);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Object path for UUID "1234" has already been registered to "/path/to/this"
     */
    public function testRegisterUuidAlreadyMapped()
    {
        $this->om->registerUuid('1234', '/path/to/this');
        $this->om->registerUuid('1234', '/path/to/that');
    }
}
