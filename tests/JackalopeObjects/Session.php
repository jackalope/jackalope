<?php
namespace jackalope\tests\JackalopeObjects;

require_once(dirname(__FILE__) . '/../inc/baseCase.php');

class Session extends \jackalope\baseCase {
    public function testConstructor() {
        $repository = $this->getMock('\jackalope\Repository', array(), array(), '', false);
        $workspaceName = 'asdfads';
        $userID = 'abcd';
        $cred = new \PHPCR\SimpleCredentials($userID, 'xxxx');
        $cred->setAttribute('test', 'toast');
        $cred->setAttribute('other', 'value');
        $transport = new \jackalope\transport\DavexClient('http://example.com');
        $s = new \jackalope\Session($repository, $workspaceName, $cred, $transport);
        $this->assertSame($repository, $s->getRepository());
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
