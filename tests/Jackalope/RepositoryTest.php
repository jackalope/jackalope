<?php

namespace Jackalope;

use PHPCR\SimpleCredentials;

class RepositoryTest extends TestCase
{
    public function testConstructor()
    {
        $factory = new Factory;
        $credentials = new SimpleCredentials('test', 'cred');
        $workspaceName = 'sadf3sd';
        $transport = $this->getMockBuilder('Jackalope\Transport\TransportInterface')
            ->disableOriginalConstructor()
            ->getMock(array('login', 'getRepositoryDescriptors', 'getNamespaces'), array($factory, 'http://example.com'));
        $transport->expects($this->once())
            ->method('login')
            ->with($this->equalTo($credentials), $this->equalTo($workspaceName))
            ->will($this->returnValue(true));
        $transport->expects($this->once())
            ->method('getRepositoryDescriptors')
            ->will($this->returnValue(array('bla'=>'bli')));
        $transport->expects($this->any())
            ->method('getNamespaces')
            ->will($this->returnValue(array()));

        $repo = new Repository($factory, $transport);
        $session = $repo->login($credentials, $workspaceName);
        $this->assertInstanceOf('Jackalope\Session', $session);

        $this->assertSame(array('bla'), $repo->getDescriptorKeys());
        $this->assertSame('bli', $repo->getDescriptor('bla'));
    }
    //descriptors are tested by jackalope-api-tests Access/RepositoryDescriptorsTest.php
}
