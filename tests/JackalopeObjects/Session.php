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
        $this->assertSame($userID, $s->getUserID());
        $this->assertSame(array('test', 'other'), $s->getAttributeNames());
        $this->assertSame('toast', $s->getAttribute('test'));
        $this->assertSame('value', $s->getAttribute('other'));
    }
    public function testLogout() {
        $this->markTestSkipped();
        //TODO: test flush object manager with the help of mock objects
    }
}
