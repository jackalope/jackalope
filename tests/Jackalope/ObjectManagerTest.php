<?php

namespace Jackalope;

use PHPUnit\Framework\MockObject\MockObject;

class ObjectManagerTest extends TestCase
{
    private ObjectManager $om;

    public function setUp(): void
    {
        $factory = new Factory();
        $session = $this->getSessionMock();
        $workspace = $session->getWorkspace();
        \assert($workspace instanceof MockObject);

        $ntMock = $this->getNodeTypeMock(['isNodeType' => true]);
        $ntmMock = $this->getNodeTypeManagerMock(['getNodeType' => $ntMock]);

        $workspace
            ->method('getNodeTypeManager')
            ->willReturn($ntmMock);

        $this->om = new ObjectManager($factory, $this->getTransportStub(), $session);
    }

    public function testGetNodeByPath(): void
    {
        $path = '/jcr:root';
        $node = $this->om->getNodeByPath($path);
        $this->assertInstanceOf(Node::class, $node);
        $children = $node->getNodes();
        $this->assertInstanceOf(\Iterator::class, $children);
        $this->assertCount(2, $children);
        $this->assertSame($node, $this->om->getNodeByPath($path));
    }

    public function testGetNodeTypes(): void
    {
        $nodetypes = $this->om->getNodeTypes();
        $this->assertIsArray($nodetypes);
        $nodetypes = $this->om->getNodeTypes(['nt:folder', 'nt:file']);
        $this->assertIsArray($nodetypes);
    }

    public function testRegisterUuid(): void
    {
        $this->om->registerUuid('1234', '/jcr:root');
        $node = $this->om->getNodeByIdentifier('1234');

        $this->assertInstanceOf(Node::class, $node);
    }

    public function testRegisterUuidAlreadyMapped(): void
    {
        $this->om->registerUuid('1234', '/path/to/this');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Object path for UUID "1234" has already been registered to "/path/to/this"');
        $this->om->registerUuid('1234', '/path/to/that');
    }
}
