<?php

namespace Jackalope;

class ObjectManagerTest extends TestCase
{
    public function testGetNodeByPath()
    {
        $factory = new Factory;
        $path = '/jcr:root';
        $om = new ObjectManager($factory, $this->getTransportStub($path), $this->getSessionMock());
        $node = $om->getNodeByPath($path);
        $this->assertInstanceOf('Jackalope\Node', $node);
        $children = $node->getNodes();
        $this->assertInstanceOf('Iterator', $children);
        $this->assertSame(2, count($children));
        $this->assertSame($node, $om->getNode($path));
    }

    public function testGetNodeTypes()
    {
        $factory = new Factory;
        $om = new ObjectManager($factory, $this->getTransportStub('/jcr:root'), $this->getSessionMock());
        $nodetypes = $om->getNodeTypes();
        $this->assertInstanceOf('DOMDocument', $nodetypes);
        $nodetypes = $om->getNodeTypes(array('nt:folder', 'nt:file'));
        $this->assertInstanceOf('DOMDocument', $nodetypes);
    }

    public function testVerifyAbsolutePath()
    {
        $factory = new Factory;
        $om = new ObjectManagerMock($factory, $this->getTransportStub('/jcr:root'), $this->getSessionMock());

        $om->verifyAbsolutePath('/jcr:root');
        $om->verifyAbsolutePath('/jcr:foo_/b-a/0^.txt');

        $this->setExpectedException('\PHPCR\RepositoryException');
        $om->verifyAbsolutePath('jcr:root');

        $this->setExpectedException('\PHPCR\RepositoryException');
        $om->verifyAbsolutePath('/jcr:root//foo');

        $this->setExpectedException('\PHPCR\RepositoryException');
        $om->verifyAbsolutePath('/jcr:root/foo?');
    }

}

class ObjectManagerMock extends ObjectManager
{
    public function verifyAbsolutePath($path)
    {
        parent::verifyAbsolutePath($path);
    }

    public function getObjectsByUuid()
    {
        return $this->objectsByUuid;
    }
}
