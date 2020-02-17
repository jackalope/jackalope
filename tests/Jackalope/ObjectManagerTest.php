<?php

namespace Jackalope;

use DOMDocument;
use Iterator;

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

        $ntMock = $this->getNodeTypeMock(['isNodeType' => true]);
        $ntmMock = $this->getNodeTypeManagerMock(['getNodeType' => $ntMock]);

        $workspace->expects($this->any())
            ->method('getNodeTypeManager')
            ->will($this->returnValue($ntmMock));

        $this->om = new ObjectManager($factory, $this->getTransportStub(), $session);
    }

    public function testGetNodeByPath()
    {
        $path = '/jcr:root';
        $node = $this->om->getNodeByPath($path);
        $this->assertInstanceOf(Node::class, $node);
        $children = $node->getNodes();
        $this->assertInstanceOf(Iterator::class, $children);
        $this->assertCount(2, $children);
        $this->assertSame($node, $this->om->getNodeByPath($path));
    }

    public function testGetNodeTypes()
    {
        $nodetypes = $this->om->getNodeTypes();
        $this->assertInstanceOf(DOMDocument::class, $nodetypes);
        $nodetypes = $this->om->getNodeTypes(['nt:folder', 'nt:file']);
        $this->assertInstanceOf(DOMDocument::class, $nodetypes);
    }

    public function testRegisterUuid()
    {
        $this->om->registerUuid('1234', '/jcr:root');
        $node = $this->om->getNodeByIdentifier('1234');

        $this->assertInstanceOf(Node::class, $node);
    }

    public function testRegisterUuidAlreadyMapped()
    {
        $this->om->registerUuid('1234', '/path/to/this');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Object path for UUID "1234" has already been registered to "/path/to/this"');
        $this->om->registerUuid('1234', '/path/to/that');
    }
}
