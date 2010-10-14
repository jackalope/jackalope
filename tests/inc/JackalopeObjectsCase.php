<?php
namespace jackalope;

require_once('baseCase.php');

class JackalopeObjectsCase extends baseCase {
    protected $JSON = '{":jcr:primaryType":"Name","jcr:primaryType":"rep:root","jcr:system":{},"tests_level1_access_base":{}}';
    
    protected function getTransportStub($path) {
        $transport = $this->getMock('\jackalope\transport\DavexClient', array('getItem', 'getNodeTypes', 'getNodePathForIdentifier'), array('http://example.com'));

        $transport->expects($this->any())
            ->method('getItem')
            ->will($this->returnValue(json_decode($this->JSON)));

        $dom = new \DOMDocument();
        $dom->load(dirname(__FILE__) . '/../fixtures/nodetypes.xml');
        $transport->expects($this->any())
            ->method('getNodeTypes')
            ->will($this->returnValue($dom));

        $transport->expects($this->any())
            ->method('getNodePathForIdentifier')
            ->will($this->returnValue('/jcr:root/uuid/to/path'));

        return $transport;
    }
    
    protected function getSessionMock() {
        return $this->getMock('\jackalope\Session', array(), array(), '', false);
    }
    
    
    protected function getNodeTypeManager() {
        $dom = new \DOMDocument();
        $dom->load(dirname(__FILE__) . '/../fixtures/nodetypes.xml');
        $om = $this->getMock('\jackalope\ObjectManager', array('getNodeTypes'), array($this->getTransportStub('/jcr:root'), $this->getSessionMock()));
        $om->expects($this->any())
            ->method('getNodeTypes')
            ->will($this->returnValue($dom));
        return new \jackalope\NodeType\NodeTypeManager($om);
    }
    
}
