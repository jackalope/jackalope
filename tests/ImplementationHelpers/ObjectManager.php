<?php
require_once(dirname(__FILE__) . '/../inc/baseCase.php');

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
}
