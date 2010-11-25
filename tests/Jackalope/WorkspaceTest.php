<?php

namespace Jackalope;

class WorkspaceTest extends TestCase
{
    public function testConstructor()
    {
        $session = $this->getMock('Jackalope\Session', array(), array(), '', false);
        $transport = $this->getMock('Jackalope\Transport\Davex\Client', array(), array('http://example.com'));
        $objManager = $this->getMock('Jackalope\ObjectManager', array(), array($session, $transport, 'a3lkjas'), '', false);
        $name = 'a3lkjas';
        $w = new Workspace($session, $objManager, $name);
        $this->assertSame($session, $w->getSession());
        $this->assertSame($name, $w->getName());
    }

    public function testGetNodeTypeManager()
    {
        $session = $this->getMock('Jackalope\Session', array(), array(), '', false);
        $transport = $this->getMock('Jackalope\Transport\Davex\Client', array(), array('http://example.com'));
        $objManager = $this->getMock('Jackalope\ObjectManager', array(), array($session, $transport, 'a3lkjas'), '', false);
        $name = 'a3lkjas';
        $w = new Workspace($session, $objManager, $name);

        $ntm = $w->getNodeTypeManager();
        $this->assertType('Jackalope\NodeType\NodeTypeManager', $ntm);
    }
}
