<?php
require_once(dirname(__FILE__) . '/../inc/JackalopeObjectsCase.php');

class OMT extends jackalope_ObjectManager {
    
    public function isUUID($i) {
        return parent::isUUID($i);
    }

    public function verifyAbsolutePath($path) {
        parent::verifyAbsolutePath($path);
    }
}

class jackalope_tests_ObjectManager extends jackalope_JackalopeObjectsCase {

    public function testGetNodeByPath() {
        $path = '/jcr:root';
        $om = new jackalope_ObjectManager($this->getTransportStub($path), $this->getSessionMock());
        $node = $om->getNodeByPath($path);
        $this->assertType('jackalope_Node', $node);
        $children = $node->getNodes();
        $this->assertType('jackalope_NodeIterator', $children);
        $this->assertEquals(2, $children->getSize());
        $this->assertEquals($node, $om->getNode($path));
    }
    
    public function testGetNodeTypes() {
        $om = new jackalope_ObjectManager($this->getTransportStub('/jcr:root'), $this->getSessionMock());
        $nodetypes = $om->getNodeTypes();
        $this->assertType('DOMDocument', $nodetypes);
        $nodetypes = $om->getNodeTypes(array('nt:folder', 'nt:file'));
        $this->assertType('DOMDocument', $nodetypes);
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
    

    public function testVerifyAbsolutePath() {
        $om = new OMT($this->getTransportStub('/jcr:root'), $this->getSessionMock());

        $om->verifyAbsolutePath('/jcr:root');
        $om->verifyAbsolutePath('/jcr:foo_/b-a/0^.txt');

        $this->setExpectedException('PHPCR_RepositoryException');
        $om->verifyAbsolutePath('jcr:root');

        $this->setExpectedException('PHPCR_RepositoryException');
        $om->verifyAbsolutePath('/jcr:root//foo');

        $this->setExpectedException('PHPCR_RepositoryException');
        $om->verifyAbsolutePath('/jcr:root/foo?');
    }

}
