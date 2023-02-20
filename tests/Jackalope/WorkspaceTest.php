<?php

namespace Jackalope;

use Jackalope\NodeType\NodeTypeManager;
use Jackalope\Transport\TransportInterface;
use PHPCR\SessionInterface;

class WorkspaceTest extends TestCase
{
    private string $name = 'a3lkjas';
    private FactoryInterface $factory;
    private SessionInterface $session;
    private ObjectManager $om;

    public function setUp(): void
    {
        $this->factory = new Factory();

        $transport = $this->getMockBuilder(TransportInterface::class)
            ->disableOriginalConstructor()
            ->getMock([])
        ;

        $this->session = $this->getSessionMock();
        $this->session
            ->method('getTransport')
            ->willReturn($transport)
        ;
        $this->om = $this->createMock(ObjectManager::class);
    }

    public function testConstructor(): void
    {
        $w = new Workspace($this->factory, $this->session, $this->om, $this->name);
        $this->assertSame($this->session, $w->getSession());
        $this->assertSame($this->name, $w->getName());
    }

    public function testGetNodeTypeManager(): void
    {
        $w = new Workspace($this->factory, $this->session, $this->om, $this->name);

        $ntm = $w->getNodeTypeManager();
        $this->assertInstanceOf(NodeTypeManager::class, $ntm);
    }
}
