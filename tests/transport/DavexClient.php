<?php
require_once(dirname(__FILE__) . '/../inc/baseCase.php');

class jackalope_transport_DavexClient_Mock extends jackalope_transport_DavexClient {
    static public function buildNodeTypesRequestMock(Array $params) {
        return self::buildNodeTypesRequest($params);
    }
    
    static public function buildReportRequestMock($name = '') {
        return self::buildReportRequest($name);
    }
}

class jackalope_tests_transport_DavexClient extends jackalope_baseCase {
    
    public function getTransportMock($args = null) {
        return $this->getMock('jackalope_transport_DavexClient', array('getDomFromBackend', 'checkLogin'), array($args));
    }
    
    public function testBuildReportRequest() {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?><foo xmlns:dcr="http://www.day.com/jcr/webdav/1.0"/>',
            jackalope_transport_DavexClient_Mock::buildReportRequestMock('foo')
        );
    }
    
    public function testGetRepositoryDescriptors() {
        $reportRequest = jackalope_transport_DavexClient_Mock::buildReportRequestMock('dcr:repositorydescriptors');
        $dom = new DOMDocument();
        $dom->load('fixtures/repositoryDescriptors.xml');
        $t = $this->getTransportMock($this->config['url']);
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->with(jackalope_transport_DavexClient_Mock::REPORT, 'http://localhost:8080/server/', $reportRequest)
            ->will($this->returnValue($dom));
        
        $desc = $t->getRepositoryDescriptors();
        $this->assertType('array', $desc);
        foreach($desc as $key => $value) {
            $this->assertType('string', $key);
            if (is_array($value)) {
                foreach($value as $val) {
                    $this->assertType('PHPCR_ValueInterface', $val);
                }
            } else {
                $this->assertType('PHPCR_ValueInterface', $value);
            }
        }
    }
    
    /**
     * @expectedException PHPCR_RepositoryException
     */
    public function testCheckLoginFail() {
        $t = new jackalope_transport_DavexClient('http://localhost:1/server');
        $t->getNodeTypes();
    }
    
    /**
     * @expectedException PHPCR_RepositoryException
     */
    public function testGetRepositoryDescriptorsNoserver() {
        $t = new jackalope_transport_DavexClient('http://localhost:1/server');
        $d = $t->getRepositoryDescriptors();
    }
    
    public function testLogin() {
        $t = new jackalope_transport_DavexClient($this->config['url']);
        $x = $t->login($this->credentials, $this->config['workspace']);
        $this->assertTrue($x);
    }
    /**
     * @expectedException PHPCR_NoSuchWorkspaceException
     */
    public function testLoginNoServer() {
        $t = new jackalope_transport_DavexClient('http://localhost:1/server');
        $t->login($this->credentials, $this->config['workspace']);
    }
    /**
     * @expectedException PHPCR_NoSuchWorkspaceException
     */
    public function testLoginNoSuchWorkspace() {
        $t = new jackalope_transport_DavexClient($this->config['url']);
        $t->login($this->credentials, 'not-an-existing-workspace');
    }
    /**
     * Should be expectedException PHPCR_LoginException
     */
    public function testLoginInvalidPw() {
        $this->markTestSkipped('make jackrabbit restrict user rights to test this');
        //$d = new jackalope_transport_DavexClient(new PHPCR_SimpleCredentials('nosuch', 'user'), $this->config['url'], $this->config['workspace']);
    }
    
    public function testGetItem() {
        $t = new jackalope_transport_DavexClient($this->config['url']);
        $t->login($this->credentials, $this->config['workspace']);
        $json = $t->getItem('/');
        $this->assertType('object', $json);
    }
    
    public function testGetNamespaces() {
        $t = new jackalope_transport_DavexClient($this->config['url']);
        $x = $t->login($this->credentials, $this->config['workspace']);
        $this->assertTrue($x);
        $ns = $t->getNamespaces();
        $this->assertType('array', $ns);
        foreach($ns as $prefix => $uri) {
            $this->assertType('string', $prefix);
            $this->assertType('string', $uri);
        }
    }
    
    /** START TESTING NODE TYPES **/
    protected function setUpNodeTypeMock($params, $fixture) {
        $dom = new DOMDocument();
        $dom->load($fixture);
        
        $requestStr = jackalope_transport_DavexClient_Mock::buildNodeTypesRequestMock($params);
        
        $t = $this->getTransportMock();
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->with(jackalope_transport_DavexClient::REPORT, '/jcr:root', $requestStr)
            ->will($this->returnValue($dom));
        return $t;
    }
    
    public function testGetAllNodeTypesRequest() {
        $xmlStr = '<?xml version="1.0" encoding="utf-8" ?><jcr:nodetypes xmlns:jcr="http://www.day.com/jcr/webdav/1.0"><jcr:all-nodetypes/></jcr:nodetypes>';
        $this->assertEquals($xmlStr, jackalope_transport_DavexClient_Mock::buildNodeTypesRequestMock(array()));
    }
    
    public function testSpecificNodeTypesRequest() {
        $xmlStr= '<?xml version="1.0" encoding="utf-8" ?><jcr:nodetypes xmlns:jcr="http://www.day.com/jcr/webdav/1.0"><jcr:nodetype><jcr:nodetypename>foo</jcr:nodetypename></jcr:nodetype><jcr:nodetype><jcr:nodetypename>bar</jcr:nodetypename></jcr:nodetype><jcr:nodetype><jcr:nodetypename>foobar</jcr:nodetypename></jcr:nodetype></jcr:nodetypes>';
        $this->assertEquals($xmlStr, jackalope_transport_DavexClient_Mock::buildNodeTypesRequestMock(array('foo', 'bar', 'foobar')));
    }
    
    public function testGetNodeTypes() {
        $t = $this->setUpNodeTypeMock(array(), 'fixtures/nodetypes.xml');
        
        $nt = $t->getNodeTypes();
        $this->assertTrue($nt instanceOf DOMDocument);
        $this->assertEquals('mix:created', $nt->firstChild->firstChild->getAttribute('name'));
    }
    
    public function testSpecificGetNodeTypes() {
        $t = $this->setUpNodeTypeMock(array('nt:folder', 'nt:file'), 'fixtures/small_nodetypes.xml');
        
        $nt = $t->getNodeTypes(array('nt:folder', 'nt:file'));
        $this->assertType('DOMDocument', $nt);
        $xp = new DOMXpath($nt);
        $res = $xp->query('//nodeType');
        $this->assertEquals(2, $res->length);
        $this->assertEquals('nt:folder', $res->item(0)->getAttribute('name'));
        $this->assertEquals('nt:file', $res->item(1)->getAttribute('name'));
    }
    
    public function testEmptyGetNodeTypes() {
        $t = $this->setUpNodeTypeMock(array(), 'fixtures/empty.xml');
        
        $this->setExpectedException('PHPCR_RepositoryException');
        $nt = $t->getNodeTypes();
    }
    
    /** END TESTING NODE TYPES **/
}
