<?php
require_once(dirname(__FILE__) . '/../inc/baseCase.php');

class jackalope_tests_Session extends jackalope_baseCase {
    public function testConstructor() {
        $repository = $this->getMock('jackalope_Repository', array(), array(), '', false);
        $workspaceName = 'asdfads';
        $userID = 'abcd';
        $cred = new PHPCR_SimpleCredentials($userID, 'xxxx');
        $cred->setAttribute('test', 'toast');
        $cred->setAttribute('other', 'value');
        //TODO: Fix this
        $transport = new jackalope_transport_DavexClient('http://example.com');
        $s = new jackalope_Session($repository, $workspaceName, $cred, $transport);
        $this->assertSame($repository, $s->getRepository());
        $this->assertEquals($userID, $s->getUserID());
        $this->assertEquals(array('test', 'other'), $s->getAttributeNames());
        $this->assertEquals('toast', $s->getAttribute('test'));
        $this->assertEquals('value', $s->getAttribute('other'));
        
        $s->getNode('/jcr:root/tests_level1_access_base/idExample');
    }
    public function testLogout() {
        $this->markTestSkipped();
        //TODO: test flush object manager with the help of mock objects
    }
}
