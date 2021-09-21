<?php

namespace Jackalope;

use Jackalope\NodeType\NodeTypeManager;
use Jackalope\Transport\TransportInterface;
use PHPCR\SessionInterface;

class WorkspaceTest extends TestCase
{
    /**
     * @var string
     */
    private $name = 'a3lkjas';

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var ObjectManager
     */
    private $om;

    public function setUp(): void
    {
        $this->factory = new Factory();

        $transport = $this->getMockBuilder(TransportInterface::class)
            ->disableOriginalConstructor()
            ->getMock([])
        ;

        $this->session = $this->getSessionMock();
        $this->session
            ->expects($this->any())
            ->method('getTransport')
            ->will($this->returnValue($transport))
        ;
        $this->om = $this->getObjectManagerMock();
    }

    public function testConstructor()
    {
        $w = new Workspace($this->factory, $this->session, $this->om, $this->name);
        $this->assertSame($this->session, $w->getSession());
        $this->assertSame($this->name, $w->getName());
    }

    public function testGetNodeTypeManager()
    {
        $w = new Workspace($this->factory, $this->session, $this->om, $this->name);

        $ntm = $w->getNodeTypeManager();
        $this->assertInstanceOf(NodeTypeManager::class, $ntm);
    }
}
