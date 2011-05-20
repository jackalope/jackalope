<?php

namespace Jackalope;

class RepositoryTest extends TestCase
{
    public function testConstructor()
    {
        $factory = new \Jackalope\Factory;
        $credentials = new \PHPCR\SimpleCredentials('test', 'cred');
        $workspaceName = 'sadf3sd';
        $transport = $this->getMock('Jackalope\Transport\Davex\Client', array('login', 'getRepositoryDescriptors'), array($factory, 'http://example.com'));
        $transport->expects($this->once())
            ->method('login')
            ->with($this->equalTo($credentials), $this->equalTo($workspaceName))
            ->will($this->returnValue(true));
        $transport->expects($this->once())
            ->method('getRepositoryDescriptors')
            ->will($this->returnValue(array('bla'=>'bli')));

        $repo = new \Jackalope\Repository($factory, null, $transport);
        $session = $repo->login($credentials, $workspaceName);
        $this->assertInstanceOf('Jackalope\Session', $session);

        $this->assertSame(array('bla'), $repo->getDescriptorKeys());
        $this->assertSame('bli', $repo->getDescriptor('bla'));
    }
    //descriptors are tested by jackalope-api-tests Access/RepositoryDescriptorsTest.php
}
