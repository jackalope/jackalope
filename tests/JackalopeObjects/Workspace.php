<?php
require_once(dirname(__FILE__) . '/../inc/baseCase.php');

class jackalope_tests_Workspace extends jackalope_baseCase {
    public function testConstructor() {
        $session = new jackalope_tests_Workspace_MockSession();
        $name = 'a3lkjas';
        $w = new jackalope_Workspace($session, $name);
        $this->assertSame($session, $w->getSession());
        $this->assertEquals($name, $w->getName());
    }
}
class jackalope_tests_Workspace_MockSession extends jackalope_Session {
    public function __construct() {
    }
}