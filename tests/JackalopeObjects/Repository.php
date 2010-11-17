<?php
namespace jackalope\tests\JackalopeObjects;

require_once(dirname(__FILE__) . '/../inc/baseCase.php');

class Repository extends \jackalope\baseCase {
    public function testConstructor() {
        $credentials = new \PHPCR\SimpleCredentials('test', 'cred');
        $workspaceName = 'sadf3sd';
        $transport = $this->getMock('\jackalope\transport\DavexClient', array('login', 'getRepositoryDescriptors'), array('http://example.com'));
        $transport->expects($this->once())
            ->method('login')
            ->with($this->equalTo($credentials), $this->equalTo($workspaceName))
            ->will($this->returnValue(true));
        $transport->expects($this->once())
            ->method('getRepositoryDescriptors')
            ->will($this->returnValue(array('bla'=>'bli')));

        $repo = new \jackalope\Repository(null, $transport);
        $session = $repo->login($credentials, $workspaceName);
        $this->assertType('\jackalope\Session', $session);

        $this->assertSame(array('bla'), $repo->getDescriptorKeys());
        $this->assertSame('bli', $repo->getDescriptor('bla'));
    }
    //descriptors are tested by jackalope-api-tests AccessTest/RepositoryDescriptors.php
}
