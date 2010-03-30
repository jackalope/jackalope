<?php
require_once(dirname(__FILE__) . '/../inc/baseCase.php');

class jackalope_tests_Workspace extends jackalope_baseCase {
    public function testConstructor() {
        $session = $this->getMock('jackalope_Session', array(), array(), '', false);
        $transport = $this->getMock('jackalope_transport_DavexClient', array(), array('http://example.com'));
        $objManager = $this->getMock('jackalope_ObjectManager', array(), array($session, $transport, 'a3lkjas'), '', false);
        $name = 'a3lkjas';
        $w = new jackalope_Workspace($session, $objManager, $name);
        $this->assertSame($session, $w->getSession());
        $this->assertEquals($name, $w->getName());
    }
    
    public function testGetNodeTypeManager() {
        $session = $this->getMock('jackalope_Session', array(), array(), '', false);
        $transport = $this->getMock('jackalope_transport_DavexClient', array(), array('http://example.com'));
        $objManager = $this->getMock('jackalope_ObjectManager', array(), array($session, $transport, 'a3lkjas'), '', false);
        $name = 'a3lkjas';
        $w = new jackalope_Workspace($session, $objManager, $name);
        
        $ntm = $w->getNodeTypeManager();
        $this->assertType('jackalope_NodeType_NodeTypeManager', $ntm);
    }
}
