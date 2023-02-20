<?php

namespace Jackalope;

use PHPCR\SimpleCredentials;

class RepositoryTest extends TestCase
{
    public function testConstructor(): void
    {
        $factory = new Factory();
        $credentials = new SimpleCredentials('test', 'cred');
        $workspaceName = 'sadf3sd';
        $transport = $this->getTransportStub();
        $transport->expects($this->once())
            ->method('login')
            ->with($this->equalTo($credentials), $this->equalTo($workspaceName))
            ->willReturn('default');
        $transport->expects($this->once())
            ->method('getRepositoryDescriptors')
            ->willReturn(['bla' => 'bli']);
        $transport
            ->method('getNamespaces')
            ->willReturn([]);

        $repo = new Repository($factory, $transport);
        $session = $repo->login($credentials, $workspaceName);
        $this->assertInstanceOf(Session::class, $session);

        $this->assertContains('bla', $repo->getDescriptorKeys());
        $this->assertSame('bli', $repo->getDescriptor('bla'));
    }

    // descriptors are tested by jackalope-api-tests Access/RepositoryDescriptorsTest.php
}
