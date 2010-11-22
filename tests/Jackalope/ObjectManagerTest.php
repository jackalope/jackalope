<?php

namespace Jackalope;

class ObjectManagerTest extends TestCase
{
    public function testGetNodeByPath()
    {
        $path = '/jcr:root';
        $om = new \jackalope\ObjectManager($this->getTransportStub($path), $this->getSessionMock());
        $node = $om->getNodeByPath($path);
        $this->assertType('jackalope\Node', $node);
        $children = $node->getNodes();
        $this->assertType('Iterator', $children);
        $this->assertSame(2, count($children));
        $this->assertSame($node, $om->getNode($path));
    }

    public function testGetNodeTypes()
    {
        $om = new \jackalope\ObjectManager($this->getTransportStub('/jcr:root'), $this->getSessionMock());
        $nodetypes = $om->getNodeTypes();
        $this->assertType('DOMDocument', $nodetypes);
        $nodetypes = $om->getNodeTypes(array('nt:folder', 'nt:file'));
        $this->assertType('DOMDocument', $nodetypes);
    }

    public function testIsUUID()
    {
        $om = new ObjectManagerMock($this->getTransportStub('/jcr:root'), $this->getSessionMock());
        $this->assertFalse($om->isUUID(''));
        $this->assertFalse($om->isUUID('/'));
        $this->assertFalse($om->isUUID('/foo'));
        $this->assertFalse($om->isUUID('../foo'));
        $this->assertFalse($om->isUUID('./foo'));
        $this->assertFalse($om->isUUID('sdfsafd'));
        $this->assertFalse($om->isUUID('842e61c0-09aba42a9-87c0-308ccc90e6f4'));
        $this->assertFalse($om->isUUID('z42e61c0-09ab-a42a9-87c0-308ccc90e6f4'));
        $this->assertFalse($om->isUUID('842e61c0a-09ab-a42a9-87c0-308ccc90e6f4'));
        $this->assertTrue($om->isUUID('842e61c0-09ab-42a9-87c0-308ccc90e6f4'));
        $this->assertTrue($om->isUUID('842E61C0-09AB-A42a-87c0-308ccc90e6f4'));
    }

    public function testNormalizePathUUID()
    {
        $uuid = '842e61c0-09ab-42a9-87c0-308ccc90e6f4';
        $path = '/jcr:root/uuid/to/path';

        $om = new ObjectManagerMock($this->getTransportStub('/jcr:root'), $this->getSessionMock());
        $this->assertSame($path, $om->normalizePath("[$uuid]"), 'Path normalization did not translate UUID into absolute path');
        // also verify it was cached
        $objectsByUuid = $om->getObjectsByUuid();
        $this->assertArrayHasKey($uuid, $objectsByUuid, 'Node UUID was not cached');
        $this->assertSame($path, $objectsByUuid[$uuid], 'Cached Node UUID path is wrong');

        $this->assertNotEquals($path, $om->normalizePath($uuid), 'Path normalization accepted improperly formatted UUID path');
    }

    /**
     * @dataProvider dataproviderAbsolutePath
     * @covers \Jackalope\ObjectManager::absolutePath
     * @covers \Jackalope\ObjectManager::normalizePath
     */
    public function testAbsolutePath($inputRoot, $inputRelPath, $output)
    {
        $om = new \jackalope\ObjectManager($this->getTransportStub('/jcr:root'), $this->getSessionMock());
        $this->assertSame($output, $om->absolutePath($inputRoot, $inputRelPath));
    }

    public static function dataproviderAbsolutePath()
    {
        return array(
            array('/',      'foo',  '/foo'),
            array('/',      '/foo', '/foo'),
            array('',       'foo',  '/foo'),
            array('',       '/foo', '/foo'),
            array('/foo',   'bar',  '/foo/bar'),
            array('/foo',   '',     '/foo'),
            array('/foo/',  'bar',  '/foo/bar'),
            array('/foo/',  '/bar', '/foo/bar'),
            array('foo',    'bar',  '/foo/bar'),

            // normalization is also part of ::absolutePath
            array('/',          '../foo',       '/foo'),
            array('/',          'foo/../bar',   '/bar'),
            array('/',          'foo/./bar',    '/foo/bar'),
            array('/foo/nope',  '../bar',       '/foo/bar'),
            array('/foo/nope',  '/../bar',      '/foo/bar'),
        );
    }

    public function testVerifyAbsolutePath()
    {
        $om = new ObjectManagerMock($this->getTransportStub('/jcr:root'), $this->getSessionMock());

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
    public function isUUID($i)
    {
        return parent::isUUID($i);
    }

    public function verifyAbsolutePath($path)
    {
        parent::verifyAbsolutePath($path);
    }

    public function getObjectsByUuid()
    {
        return $this->objectsByUuid;
    }
}
