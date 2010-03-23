<?php
require_once(dirname(__FILE__) . '/baseCase.php');

class jackalope_JackalopeObjectsCase extends jackalope_baseCase {
    protected $JSON = '{":jcr:primaryType":"Name","jcr:primaryType":"rep:root","jcr:system":{},"tests_level1_access_base":{}}';
    
    protected function getTransportStub($path) {
        $transport = $this->getMock('jackalope_transport_DavexClient', array('getItem', 'getNodeTypes'), array('http://example.com'));
        $transport->expects($this->any())
            ->method('getItem')
            ->will($this->returnValue(json_decode($this->JSON)));
        $dom = new DOMDocument();
        $dom->load(dirname(__FILE__) . '/../fixtures/nodetypes.xml');
        $transport->expects($this->any())
            ->method('getNodeTypes')
            ->will($this->returnValue($dom));
        return $transport;
    }
    
    protected function getSessionMock() {
        return $this->getMock('jackalope_Session', array(), array(), '', false);
    }
}
