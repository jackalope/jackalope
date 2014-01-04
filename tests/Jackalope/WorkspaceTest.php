<?php

namespace Jackalope;

class WorkspaceTest extends TestCase
{
    private $name = 'a3lkjas';
    private $factory;
    private $session;
    private $om;

    public function setUp()
    {
        $this->factory = new Factory;

        $transport = $this->getMockBuilder('Jackalope\Transport\TransportInterface')
            ->disableOriginalConstructor()
            ->getMock(array())
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
        $this->assertInstanceOf('Jackalope\NodeType\NodeTypeManager', $ntm);
    }
}
