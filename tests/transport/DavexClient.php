<?php
require_once(dirname(__FILE__) . '/../inc/baseCase.php');

class jackalope_transport_DavexClient_Mock extends jackalope_transport_DavexClient {
    public $curl;
    public $server;
    
    static public function buildNodeTypesRequestMock(Array $params) {
        return self::buildNodeTypesRequest($params);
    }
    
    static public function buildReportRequestMock($name = '') {
        return self::buildReportRequest($name);
    }
    
    static public function buildPropfindRequestMock($args = array()) {
        return self::buildPropfindRequest($args);
    }
    
    public function initConnection() {
        return parent::initConnection();
    }
    
    public function closeConnection() {
        return parent::closeConnection();
    }
    
    public function prepareRequest($type, $uri, $body = '', $depth = 0) {
        return parent::prepareRequest($type, $uri, $body, $depth);
    }
    
    public function setCredentials($credentials) {
        $this->credentials = $credentials;
    }
}

class jackalope_tests_transport_DavexClient extends jackalope_baseCase {
    
    public function getTransportMock($args = 'testuri') {
        return $this->getMock(
            'jackalope_transport_DavexClient_Mock',
            array('getDomFromBackend', 'getJsonFromBackend', 'checkLogin', 'initConnection', '__destruct', '__construct'),
            array($args)
        );
    }
    
    public function getCurlFixture() {
        return $this->getMock('jackalope_transport_curl');
    }
    
    /**
     * @covers jackalope_transport_DavexClient::__construct
     */
    public function testConstructor() {
        $transport = new jackalope_transport_DavexClient_Mock('testuri');
        $this->assertEquals('testuri/', $transport->server);
    }
    
    /**
     * @covers jackalope_transport_DavexClient::__destruct
     */
    public function testDestructor() {
        $transport = $this->getTransportMock();
        $transport->__destruct();
        $this->assertEquals(null, $transport->curl);
    }
    
    /**
     * @covers jackalope_transport_DavexClient::initConnection
     */
    public function testInitConnectionAlreadInitialized() {
        $t = $this->getMock(
            'jackalope_transport_DavexClient_Mock',
            array('__destruct', '__construct'),
            array('testuri')
        );
        $t->curl = 'test';
        $this->assertFalse($t->initConnection());
        $this->assertEquals('test', $t->curl);
    }

    /**
     * @covers jackalope_transport_DavexClient::initConnection
     */
    public function testInitConnection() {
        $t = $this->getMock(
            'jackalope_transport_DavexClient_Mock',
            array('__destruct', '__construct'),
            array('testuri')
        );
        $t->initConnection();
        $this->assertType('jackalope_transport_curl', $t->curl);
    }
    
    /**
     * @covers jackalope_transport_DavexClient::closeConnection
     */
    public function testCloseConnectionAlreadyClosed() {
        $t = $this->getTransportMock();
        $t->curl = null;
        $this->assertFalse($t->closeConnection());
    }
    
    /**
     * @covers jackalope_transport_DavexClient::closeConnection
     */
    public function testCloseConnection() {
        $t = $this->getTransportMock();
        $t->curl = $this->getCurlFixture();
        $t->curl->expects($this->once())
            ->method('close');
        $t->closeConnection();
        $this->assertEquals(null, $t->curl);
    }
    
    /**
     * @covers jackalope_transport_DavexClient::prepareRequest
     */
    public function testPrepareRequest() {
        $t = $this->getTransportMock();
        $t->curl = $this->getMock('jackalope_transport_curl', array());
        $t->curl->expects($this->at(0))
            ->method('setopt')
            ->with(CURLOPT_CUSTOMREQUEST, 'testmethod');
        $t->curl->expects($this->at(1))
            ->method('setopt')
            ->with(CURLOPT_URL, 'testuri');
        $t->curl->expects($this->at(2))
            ->method('setopt')
            ->with(CURLOPT_RETURNTRANSFER, 1);
        $t->curl->expects($this->at(3))
            ->method('setopt')
            ->with(CURLOPT_HTTPHEADER, array(
                'Depth: 3',
                'Content-Type: text/xml; charset=UTF-8',
                'User-Agent: '. jackalope_transport_DavexClient::USER_AGENT
            ));
        $t->curl->expects($this->at(4))
            ->method('setopt')
            ->with(CURLOPT_POSTFIELDS, 'testbody');
        $t->prepareRequest('testmethod', 'testuri', 'testbody', 3);
    }
    
    /**
     * @covers jackalope_transport_DavexClient::getRawFromBackend
     */
    public function testGetRawFromBackend() {
        $t = $this->getTransportMock();
    }
    
    /**
     * @covers jackalope_transport_DavexClient::getJsonFromBackend
     */
    public function testGetJsonFromBackend() {
        
    }
    
    /**
     * @covers jackalope_transport_DavexClient::getDomFromBackend
     */
    public function testGetDomFromBackend() {
        
    }
    
    /**
     * @covers jackalope_transport_DavexClient::buildReportRequest
     */
    public function testBuildReportRequest() {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?><foo xmlns:dcr="http://www.day.com/jcr/webdav/1.0"/>',
            jackalope_transport_DavexClient_Mock::buildReportRequestMock('foo')
        );
    }
    
    /**
     * @covers jackalope_transport_DavexClient::getRepositoryDescriptors
     */
    public function testGetRepositoryDescriptors() {
        $reportRequest = jackalope_transport_DavexClient_Mock::buildReportRequestMock('dcr:repositorydescriptors');
        $dom = new DOMDocument();
        $dom->load('fixtures/repositoryDescriptors.xml');
        $t = $this->getTransportMock();
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->with(jackalope_transport_DavexClient_Mock::REPORT, 'testuri/', $reportRequest)
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
     * @covers jackalope_transport_DavexClient::checkLogin
     * @expectedException PHPCR_RepositoryException
     */
    public function testCheckLoginFail() {
        $t = new jackalope_transport_DavexClient('http://localhost:1/server');
        $t->getNodeTypes();
    }
    
    /**
     * @covers jackalope_transport_DavexClient::getRepositoryDescriptors
     * @expectedException PHPCR_RepositoryException
     */
    public function testGetRepositoryDescriptorsNoserver() {
        $t = new jackalope_transport_DavexClient('http://localhost:1/server');
        $d = $t->getRepositoryDescriptors();
    }
    
    /**
     * @covers jackalope_transport_DavexClient::buildPropfindRequest
     */
    public function testBuildPropfindRequestSingle() {
        $xmlStr = '<?xml version="1.0" encoding="UTF-8"?><D:propfind xmlns:D="DAV:" xmlns:dcr="http://www.day.com/jcr/webdav/1.0"><D:prop>';
        $xmlStr .= '<foo/>';
        $xmlStr .= '</D:prop></D:propfind>';
        $this->assertEquals($xmlStr, jackalope_transport_DavexClient_Mock::buildPropfindRequestMock('foo'));
    }
    
    /**
     * @covers jackalope_transport_DavexClient::buildPropfindRequest
     */
    public function testBuildPropfindRequestArray() {
        $xmlStr = '<?xml version="1.0" encoding="UTF-8"?><D:propfind xmlns:D="DAV:" xmlns:dcr="http://www.day.com/jcr/webdav/1.0"><D:prop>';
        $xmlStr .= '<foo/><bar/>';
        $xmlStr .= '</D:prop></D:propfind>';
        $this->assertEquals($xmlStr, jackalope_transport_DavexClient_Mock::buildPropfindRequestMock(array('foo', 'bar')));
    }
    
    /**
     * @covers jackalope_transport_DavexClient::login
     * @expectedException PHPCR_RepositoryException
     */
    public function testLoginAlreadyLoggedin() {
        $t = $this->getTransportMock();
        $t->setCredentials('test');
        $t->login($this->credentials, $this->config['workspace']);
    }
    
    /**
     * @covers jackalope_transport_DavexClient::login
     * @expectedException PHPCR_LoginException
     */
    public function testLoginUnsportedCredentials() {
        $t = $this->getTransportMock();
        $t->login(new falseCredentialsMock(), $this->config['workspace']);
    }

    /**
     * @covers jackalope_transport_DavexClient::login
     * @expectedException PHPCR_RepositoryException
     */
    public function testLoginEmptyBackendResponse() {
        $dom = new DOMDocument();
        $dom->load('fixtures/empty.xml');
        $t = $this->getTransportMock();
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->will($this->returnValue($dom));
        $t->login($this->credentials, $this->config['workspace']);
    }

    /**
     * @covers jackalope_transport_DavexClient::login
     * @expectedException PHPCR_RepositoryException
     */
    public function testLoginWrongWorkspace() {
        $dom = new DOMDocument();
        $dom->load('fixtures/wrongWorkspace.xml');
        $t = $this->getTransportMock();
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->will($this->returnValue($dom));
        $t->login($this->credentials, $this->config['workspace']);
    }
    
     /**
     * @covers jackalope_transport_DavexClient::login
     */
    public function testLogin() {
        $propfindRequest = jackalope_transport_DavexClient_Mock::buildPropfindRequestMock(array('D:workspace', 'dcr:workspaceName'));
        $dom = new DOMDocument();
        $dom->load('fixtures/loginResponse.xml');
        $t = $this->getTransportMock();
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->with(jackalope_transport_DavexClient::PROPFIND, 'testuri/tests', $propfindRequest)
            ->will($this->returnValue($dom));
        
        $x = $t->login($this->credentials, $this->config['workspace']);
        $this->assertTrue($x);
    }
    
    /**
     * @covers jackalope_transport_DavexClient::login
     * @expectedException PHPCR_NoSuchWorkspaceException
     */
    public function testLoginNoServer() {
        $t = new jackalope_transport_DavexClient('http://localhost:1/server');
        $t->login($this->credentials, $this->config['workspace']);
    }
    
    /**
     * @covers jackalope_transport_DavexClient::login
     * @expectedException PHPCR_NoSuchWorkspaceException
     */
    public function testLoginNoSuchWorkspace() {
        $t = new jackalope_transport_DavexClient($this->config['url']);
        $t->login($this->credentials, 'not-an-existing-workspace');
    }
    
    /**
     * Should be expectedException PHPCR_LoginException
     * @covers jackalope_transport_DavexClient::login
     */
    public function testLoginInvalidPw() {
        $this->markTestSkipped('make jackrabbit restrict user rights to test this');
        //$d = new jackalope_transport_DavexClient(new PHPCR_SimpleCredentials('nosuch', 'user'), $this->config['url'], $this->config['workspace']);
    }
    
    /**
     * @covers jackalope_transport_DavexClient::getItem
     * @expectedException PHPCR_RepositoryException
     */
    public function testGetItemWithoutAbsPath() {
        $t = $this->getTransportMock();
        $t->getItem('foo');
    }
    
    /**
     * @covers jackalope_transport_DavexClient::getItem
     */
    public function testGetItem() {
        $t = $this->getTransportMock($this->config['url']);
        $t->expects($this->once())
            ->method('getJsonFromBackend')
            ->with(jackalope_transport_DavexClient::GET, '/foobar.0.json');
        
        $json = $t->getItem('/foobar');
    }
    
    /**
     * @covers jackalope_transport_DavexClient::getNamespaces
     */
    public function testGetNamespaces() {
        $reportRequest = jackalope_transport_DavexClient_Mock::buildReportRequestMock('dcr:registerednamespaces');
        $dom = new DOMDocument();
        $dom->load('fixtures/registeredNamespaces.xml');
        
        $t = $this->getTransportMock($this->config['url']);
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->with(jackalope_transport_DavexClient::REPORT, '', $reportRequest)
            ->will($this->returnValue($dom));
        
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
    
    /**
     * @covers jackalope_transport_DavexClient::buildNodeTypesRequest
     */
    public function testGetAllNodeTypesRequest() {
        $xmlStr = '<?xml version="1.0" encoding="utf-8" ?><jcr:nodetypes xmlns:jcr="http://www.day.com/jcr/webdav/1.0"><jcr:all-nodetypes/></jcr:nodetypes>';
        $this->assertEquals($xmlStr, jackalope_transport_DavexClient_Mock::buildNodeTypesRequestMock(array()));
    }
    
    /**
     * @covers jackalope_transport_DavexClient::buildNodeTypesRequest
     */
    public function testSpecificNodeTypesRequest() {
        $xmlStr= '<?xml version="1.0" encoding="utf-8" ?><jcr:nodetypes xmlns:jcr="http://www.day.com/jcr/webdav/1.0"><jcr:nodetype><jcr:nodetypename>foo</jcr:nodetypename></jcr:nodetype><jcr:nodetype><jcr:nodetypename>bar</jcr:nodetypename></jcr:nodetype><jcr:nodetype><jcr:nodetypename>foobar</jcr:nodetypename></jcr:nodetype></jcr:nodetypes>';
        $this->assertEquals($xmlStr, jackalope_transport_DavexClient_Mock::buildNodeTypesRequestMock(array('foo', 'bar', 'foobar')));
    }
    
    /**
     * @covers jackalope_transport_DavexClient::getNodeTypes
     */
    public function testGetNodeTypes() {
        $t = $this->setUpNodeTypeMock(array(), 'fixtures/nodetypes.xml');
        
        $nt = $t->getNodeTypes();
        $this->assertTrue($nt instanceOf DOMDocument);
        $this->assertEquals('mix:created', $nt->firstChild->firstChild->getAttribute('name'));
    }
    
    /**
     * @covers jackalope_transport_DavexClient::getNodeTypes
     */
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
    
    /**
     * @covers jackalope_transport_DavexClient::getNodeTypes
     */
    public function testEmptyGetNodeTypes() {
        $t = $this->setUpNodeTypeMock(array(), 'fixtures/empty.xml');
        
        $this->setExpectedException('PHPCR_RepositoryException');
        $nt = $t->getNodeTypes();
    }
    
    /** END TESTING NODE TYPES **/
}

class falseCredentialsMock implements PHPCR_CredentialsInterface {
    
}
