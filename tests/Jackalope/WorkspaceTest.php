<?php

namespace Jackalope;

class WorkspaceTest extends TestCase
{
    private $name = 'a3lkjas';
    private $factory;
    private $session;
    private $objManager;

    public function setUp()
    {
        $this->factory = new Factory;

        $transport = $this->getMockBuilder('Jackalope\Transport\TransportInterface')
            ->disableOriginalConstructor()
            ->getMock(array())
        ;

        $this->session = $this->getMock('Jackalope\Session', array(), array($this->factory), '', false);
        $this->session
            ->expects($this->any())
            ->method('getTransport')
            ->will($this->returnValue($transport))
        ;
        $this->objManager = $this->getMock('Jackalope\ObjectManager', array(), array($this->factory, $this->session, $transport, $this->name), '', false);
    }
    public function testConstructor()
    {
        $w = new Workspace($this->factory, $this->session, $this->objManager, $this->name);
        $this->assertSame($this->session, $w->getSession());
        $this->assertSame($this->name, $w->getName());
    }

    public function testGetNodeTypeManager()
    {
        $w = new Workspace($this->factory, $this->session, $this->objManager, $this->name);

        $ntm = $w->getNodeTypeManager();
        $this->assertInstanceOf('Jackalope\NodeType\NodeTypeManager', $ntm);
    }
}
