<?php

namespace Jackalope;

class WorkspaceTest extends TestCase
{
    public function testConstructor()
    {
        $factory = new \Jackalope\Factory;
        $session = $this->getMock('Jackalope\Session', array(), array($factory), '', false);
        $transport = $this->getMock('Jackalope\Transport\Davex\Client', array(), array($factory, 'http://example.com'));
        $objManager = $this->getMock('Jackalope\ObjectManager', array(), array($factory, $session, $transport, 'a3lkjas'), '', false);
        $name = 'a3lkjas';
        $w = new Workspace($factory, $session, $objManager, $name);
        $this->assertSame($session, $w->getSession());
        $this->assertSame($name, $w->getName());
    }

    public function testGetNodeTypeManager()
    {
        $factory = new \Jackalope\Factory;
        $session = $this->getMock('Jackalope\Session', array(), array($factory), '', false);
        $transport = $this->getMock('Jackalope\Transport\Davex\Client', array(), array($factory, 'http://example.com'));
        $objManager = $this->getMock('Jackalope\ObjectManager', array(), array($factory, $session, $transport, 'a3lkjas'), '', false);
        $name = 'a3lkjas';


        $w = new Workspace($factory, $session, $objManager, $name);

        $ntm = $w->getNodeTypeManager();
        $this->assertInstanceOf('Jackalope\NodeType\NodeTypeManager', $ntm);
    }
}
