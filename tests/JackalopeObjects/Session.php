<?php
require_once(dirname(__FILE__) . '/../inc/baseCase.php');

class jackalope_tests_Session extends jackalope_baseCase {
    public function testConstructor() {
        $mock = new jackalope_tests_Session_MockRepository();
        $workspaceName = 'asdfads';
        $userID = 'abcd';
        $cred = new PHPCR_SimpleCredentials($userID, 'xxxx');
        $cred->setAttribute('test', 'toast');
        $cred->setAttribute('other', 'value');
        $s = new jackalope_Session($mock, $workspaceName, $cred);
        $this->assertSame($mock, $s->getRepository());
        $this->assertEquals($userID, $s->getUserID());
        $this->assertEquals(array('test', 'other'), $s->getAttributeNames());
        $this->assertEquals('toast', $s->getAttribute('test'));
        $this->assertEquals('value', $s->getAttribute('other'));
    }
    public function testLogout() {
        $this->markTestSkipped();
        //TODO: test flush object manager with the help of mock objects
    }
}
class jackalope_tests_Session_MockRepository extends jackalope_Repository {
    public function __construct() {
    }
}
