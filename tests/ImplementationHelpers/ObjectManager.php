<?php
require_once(dirname(__FILE__) . '/../inc/baseCase.php');

class OMT extends jackalope_ObjectManager {
    public function absolutePath($root, $relativePath) {
        return parent::absolutePath($root, $relativePath);
    }
    
    public function isUUID($i) {
        return parent::isUUID($i);
    }
}

class jackalope_tests_ObjectManager extends jackalope_baseCase {
    private $JSON = '{":jcr:primaryType":"Name","jcr:primaryType":"rep:root","jcr:system":{},"tests_level1_access_base":{}}';
    public function testGetNodeByPath() {
        $path = '/jcr:root';
        $om = new jackalope_ObjectManager($this->getTransportStub($path), $this->getSessionMock());
        $node = $om->getNodeByPath($path);
        $this->assertType('jackalope_Node', $node);
        $children = $node->getNodes();
        $this->assertType('jackalope_NodeIterator', $children);
        $this->assertEquals(2, $children->getSize());
    }

    private function getTransportStub($path) {
        $transport = $this->getMock('jackalope_transport_DavexClient', array('getItem'), array('http://example.com'));
        $transport->expects($this->any())
            ->method('getItem')
            ->will($this->returnValue(json_decode($this->JSON)));
        return $transport;
    }
    private function getSessionMock() {
        return $this->getMock('jackalope_Session', array(), array(), '', false);
    }
    
    public function testIsUUID() {
        $om = new OMT($this->getTransportStub('/jcr:root'), $this->getSessionMock());
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
    
    public function testAbsolutePath() {
        $om = new OMT($this->getTransportStub('/jcr:root'), $this->getSessionMock());
        $this->assertEquals('/jcr:root/', $om->absolutePath('/', 'jcr:root'));
        $this->assertEquals('/jcr:root/', $om->absolutePath('/', './jcr:root'));
        $this->assertEquals('/jcr:root/', $om->absolutePath('/jcr:root', ''));
        $this->assertEquals('/jcr:root/foo_/b-a/0^/', $om->absolutePath('jcr:root', 'foo_/b-a/0^/'));
        $this->assertEquals('/jcr:root/foo_/b-a/0^/', $om->absolutePath('/jcr:root', '/foo_/b-a/0^/'));
        $this->assertEquals('/jcr:root/foo_/b-a/0^/', $om->absolutePath('jcr:root/', '/foo_/b-a/0^'));
        $this->assertEquals('/jcr:root/foo/bar/', $om->absolutePath('/jcr:root/wrong/', '../foo/bar/'));
        $this->assertEquals('/jcr:root/foo/bar/', $om->absolutePath('/jcr:root/wrong/', '/../foo/bar/'));
        $this->assertEquals('/jcr:root/foo/bar/', $om->absolutePath('/jcr:root/wrong/', '/foo/../../foo/bar/'));
    }
}
