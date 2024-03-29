<?php

namespace Jackalope;

use PHPCR\SimpleCredentials;

class SessionTest extends TestCase
{
    public function testConstructor(): void
    {
        $factory = new Factory();
        $repository = $this->getRepositoryMock();
        $workspaceName = 'asdfads';
        $userID = 'abcd';
        $cred = new SimpleCredentials($userID, 'xxxx');
        $cred->setAttribute('test', 'toast');
        $cred->setAttribute('other', 'value');
        $transport = $this->getTransportStub();
        $transport
            ->method('getNamespaces')
            ->willReturn([]);
        $s = new Session($factory, $repository, $workspaceName, $cred, $transport);
        $this->assertSame($repository, $s->getRepository());
        $this->assertSame($userID, $s->getUserID());
        $this->assertSame(['test', 'other'], $s->getAttributeNames());
        $this->assertSame('toast', $s->getAttribute('test'));
        $this->assertSame('value', $s->getAttribute('other'));
    }

    public function testLogoutAndRegistry(): void
    {
        $factory = new Factory();
        $repository = $this->getRepositoryMock();
        $transport = $this->getTransportStub();
        $transport->expects($this->once())
            ->method('logout');
        $session = new Session($factory, $repository, 'x', new SimpleCredentials('foo', 'bar'), $transport);
        $this->assertTrue($session->isLive());
        $key = $session->getRegistryKey();
        $this->assertSame($session, Session::getSessionFromRegistry($key));
        $session->logout();
        $this->assertFalse($session->isLive());
        $this->assertNull(Session::getSessionFromRegistry($key));
    }

    public function testSessionRegistry(): void
    {
        $factory = new Factory();
        $repository = $this->getRepositoryMock();
        $transport = $this->getTransportStub();
        $transport
            ->method('getNamespaces')
            ->willReturn([]);

        $s = new Session($factory, $repository, 'workspaceName', new SimpleCredentials('foo', 'bar'), $transport);

        $this->assertSame(Session::getSessionFromRegistry($s->getRegistryKey()), $s);
        $s->logout();
        $this->assertNull(Session::getSessionFromRegistry($s->getRegistryKey()));
    }
}
