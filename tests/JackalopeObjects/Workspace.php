<?php
namespace jackalope\tests\JackalopeObjects;

require_once(dirname(__FILE__) . '/../inc/baseCase.php');

class Workspace extends \jackalope\baseCase {
    public function testConstructor() {
        $session = $this->getMock('\jackalope\Session', array(), array(), '', false);
        $transport = $this->getMock('\jackalope\transport\DavexClient', array(), array('http://example.com'));
        $objManager = $this->getMock('\jackalope\ObjectManager', array(), array($session, $transport, 'a3lkjas'), '', false);
        $name = 'a3lkjas';
        $w = new \jackalope\Workspace($session, $objManager, $name);
        $this->assertSame($session, $w->getSession());
        $this->assertEquals($name, $w->getName());
    }

    public function testGetNodeTypeManager() {
        $session = $this->getMock('\jackalope\Session', array(), array(), '', false);
        $transport = $this->getMock('\jackalope\transport\DavexClient', array(), array('http://example.com'));
        $objManager = $this->getMock('\jackalope\ObjectManager', array(), array($session, $transport, 'a3lkjas'), '', false);
        $name = 'a3lkjas';
        $w = new \jackalope\Workspace($session, $objManager, $name);

        $ntm = $w->getNodeTypeManager();
        $this->assertType('\jackalope\NodeType\NodeTypeManager', $ntm);
    }
}
