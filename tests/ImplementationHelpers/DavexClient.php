<?php
namespace jackalope\tests\ImplementationHelpers;
use \DOMDocument, \DOMXPath;

require_once(dirname(__FILE__) . '/../inc/baseCase.php');

class DavexClient_Mock extends \jackalope\transport\DavexClient {
    public $curl;
    public $server = 'testserver';
    public $workspace = 'testWorkspace';
    public $workspaceUri = 'testWorkspaceUri';
    public $workspaceUriRoot = 'testWorkspaceUriRoot';

    static public function buildNodeTypesRequestMock(Array $params) {
        return self::buildNodeTypesRequest($params);
    }

    static public function buildReportRequestMock($name = '') {
        return self::buildReportRequest($name);
    }

    static public function buildPropfindRequestMock($args = array()) {
        return self::buildPropfindRequest($args);
    }

    static public function buildLocateRequestMock($arg = '') {
        return self::buildLocateRequest($arg);
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

    public function checkLogin() {
        parent::checkLogin();
    }

    public function getRawFromBackend() {
        return parent::getRawFromBackend();
    }

    public function getDomFromBackend($type, $uri, $body='', $depth=0) {
        return parent::getDomFromBackend($type, $uri, $body, $depth);
    }

    public function getJsonFromBackend($type, $uri, $body='', $depth=0) {
        return parent::getJsonFromBackend($type, $uri, $body, $depth);
    }
}

class DavexClient extends \jackalope\baseCase {

    public function getTransportMock($args = 'testuri', $changeMethods = array()) {
        //Array XOR
        $defaultMockMethods = array('getDomFromBackend', 'getJsonFromBackend', 'checkLogin', 'initConnection', '__destruct', '__construct');
        $mockMethods = array_merge(array_diff($defaultMockMethods, $changeMethods), array_diff($changeMethods, $defaultMockMethods));
        return $this->getMock(
            '\jackalope\tests\ImplementationHelpers\DavexClient_Mock',
            $mockMethods,
            array($args)
        );
    }

    public function getCurlFixture($fixture = null, $errno = null) {
        $curl =  $this->getMock('jackalope\transport\curl');
        if (isset($fixture)) {
            if (is_file($fixture)) {
                $fixture = file_get_contents($fixture);
            }
            $curl->expects($this->any())
                ->method('exec')
                ->will($this->returnValue($fixture));
        }

        if (isset($errno)) {
            $curl->expects($this->any())
                ->method('errno')
                ->will($this->returnValue($errno));
        }
        return $curl;
    }

    /**
     * @covers \jackalope\transport\DavexClient::__construct
     */
    public function testConstructor() {
        $transport = new \jackalope\tests\ImplementationHelpers\DavexClient_Mock('testuri');
        $this->assertEquals('testuri/', $transport->server);
    }

    /**
     * @covers \jackalope\transport\DavexClient::__destruct
     */
    public function testDestructor() {
        $transport = new \jackalope\tests\ImplementationHelpers\DavexClient_Mock('testuri');
        $transport->__destruct();
        $this->assertEquals(null, $transport->curl);
    }

    /**
     * @covers \jackalope\transport\DavexClient::initConnection
     */
    public function testInitConnectionAlreadInitialized() {
        $t = $this->getMock(
            '\jackalope\tests\ImplementationHelpers\DavexClient_Mock',
            array('__destruct', '__construct'),
            array('testuri')
        );
        $t->curl = 'test';
        $this->assertFalse($t->initConnection());
        $this->assertEquals('test', $t->curl);
    }

    /**
     * @covers \jackalope\transport\DavexClient::initConnection
     */
    public function testInitConnection() {
        $t = $this->getMock(
            '\jackalope\tests\ImplementationHelpers\DavexClient_Mock',
            array('__destruct', '__construct'),
            array('testuri')
        );
        $t->initConnection();
        $this->assertType('jackalope\transport\curl', $t->curl);
    }

    /**
     * @covers \jackalope\transport\DavexClient::closeConnection
     */
    public function testCloseConnectionAlreadyClosed() {
        $t = $this->getTransportMock();
        $t->curl = null;
        $this->assertFalse($t->closeConnection());
    }

    /**
     * @covers \jackalope\transport\DavexClient::closeConnection
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
     * @covers \jackalope\transport\DavexClient::prepareRequest
     */
    public function testPrepareRequest() {
        $t = $this->getTransportMock();
        $t->curl = $this->getMock('\jackalope\transport\curl', array());
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
                'User-Agent: '. \jackalope\transport\DavexClient::USER_AGENT
            ));
        $t->curl->expects($this->at(4))
            ->method('setopt')
            ->with(CURLOPT_POSTFIELDS, 'testbody');
        $t->prepareRequest('testmethod', 'testuri', 'testbody', 3);
    }

    /**
     * @covers \jackalope\transport\DavexClient::prepareRequest
     */
    public function testPrepareRequestWithCredentials() {
        $t = $this->getTransportMock();
        $t->setCredentials(new \PHPCR\SimpleCredentials('foo', 'bar'));
        $t->curl = $this->getMock('\jackalope\transport\curl', array());
        $t->curl->expects($this->at(0))
            ->method('setopt')
            ->with(CURLOPT_USERPWD, 'foo:bar');
        $t->curl->expects($this->at(1))
            ->method('setopt')
            ->with(CURLOPT_CUSTOMREQUEST, 'testmethod');
        $t->prepareRequest('testmethod', 'testuri', 'testbody', 3);
    }

    /**
     * @covers \jackalope\transport\DavexClient::getRawFromBackend
     */
    public function testGetRawFromBackend() {
        $t = $this->getTransportMock();
        $t->curl = $this->getCurlFixture('hulla hulla');
        $this->assertEquals('hulla hulla', $t->getRawFromBackend());
    }

    /**
     * @covers \jackalope\transport\DavexClient::getRawFromBackend
     * @expectedException \PHPCR\NoSuchWorkspaceException
     */
    public function testGetRawFromBackendNoHost() {
        $t = $this->getTransportMock();
        $t->curl = $this->getCurlFixture(null, CURLE_COULDNT_RESOLVE_HOST);
        $t->getRawFromBackend();
    }

    /**
     * @covers \jackalope\transport\DavexClient::getRawFromBackend
     * @expectedException \PHPCR\NoSuchWorkspaceException
     */
    public function testGetRawFromBackendNoConnect() {
        $t = $this->getTransportMock();
        $t->curl = $this->getCurlFixture(null, CURLE_COULDNT_CONNECT);
        $t->getRawFromBackend();
    }

    /**
     * @covers \jackalope\transport\DavexClient::getRawFromBackend
     * @expectedException \PHPCR\RepositoryException
     */
    public function testGetRawFromBackendNoData() {
        $t = $this->getTransportMock();
        $t->curl = $this->getCurlFixture(null);
        $t->getRawFromBackend();
    }

    /**
     * @covers \jackalope\transport\DavexClient::getJsonFromBackend
     */
    public function testGetJsonFromBackend() {
        $fixture = json_decode(file_get_contents('fixtures/empty.json'));

        $t = $this->getTransportMock('testuri', array('getJsonFromBackend', 'prepareRequest'));
        $t->curl = $this->getCurlFixture('fixtures/empty.json');
        $t->expects($this->once())
            ->method('prepareRequest')
            ->with('GET', 'foo', 'bar', 1);
        $json = $t->getJsonFromBackend('GET', 'foo', 'bar', 1);
        $this->assertEquals($fixture, $json);
    }

    /**
     * @covers \jackalope\transport\DavexClient::getJsonFromBackend
     * @expectedException \PHPCR\ItemNotFoundException
     */
    public function testGetJsonFromBackendItemNotFound() {
        $t = $this->getTransportMock('testuri', array('getJsonFromBackend', 'prepareRequest'));
        $t->curl = $this->getCurlFixture('fixtures/empty.xml');
        $t->curl->expects($this->any())
            ->method('getinfo')
            ->with(CURLINFO_HTTP_CODE)
            ->will($this->returnValue(404));
        $t->expects($this->once())
            ->method('prepareRequest')
            ->with('POST', 'hulla', '', 0);
        $t->getJsonFromBackend('POST', 'hulla');
    }

    /**
     * @covers \jackalope\transport\DavexClient::getJsonFromBackend
     * @expectedException \PHPCR\RepositoryException
     */
    public function testGetJsonFromBackendRepositoryException() {
        $t = $this->getTransportMock('testuri', array('getJsonFromBackend', 'prepareRequest'));
        $t->curl = $this->getCurlFixture('fixtures/empty.xml');
        $t->curl->expects($this->any())
            ->method('getinfo')
            ->will($this->returnValue(array('http_code' => 500)));
        $t->getJsonFromBackend('POST', 'hulla');
    }

    /**
     * @covers \jackalope\transport\DavexClient::getJsonFromBackend
     * @expectedException \PHPCR\RepositoryException
     */
    public function testGetJsonFromBackendInvalidJson() {
        $t = $this->getTransportMock('testuri', array('getJsonFromBackend', 'prepareRequest'));
        $t->curl = $this->getCurlFixture('invalid json');
        $t->getJsonFromBackend('POST', 'hulla');
    }

    /**
     * @covers \jackalope\transport\DavexClient::getDomFromBackend
     */
    public function testGetDomFromBackend() {
        $t = $this->getTransportMock('testuri', array('getDomFromBackend', 'prepareRequest'));
        $t->curl = $this->getCurlFixture('fixtures/empty.xml');
        $t->expects($this->once())
            ->method('prepareRequest')
            ->with('GET', 'foo', 'bar', 1);
        $dom = $t->getDomFromBackend('GET', 'foo', 'bar', 1);
        $this->assertXmlStringEqualsXmlFile('fixtures/empty.xml', $dom->saveXML());
    }


    /**
     * @covers \jackalope\transport\DavexClient::getDomFromBackend
     * @expectedException \PHPCR\NoSuchWorkspaceException
     */
    public function testGetDomFromBackendNoWorkspace() {
        $t = $this->getTransportMock('testuri', array('getDomFromBackend', 'prepareRequest'));
        $t->curl = $this->getCurlFixture('fixtures/exceptionNoWorkspace.xml');
        $t->expects($this->once())
            ->method('prepareRequest')
            ->with('POST', 'hulla', '', 0);
        $t->getDomFromBackend('POST', 'hulla');
    }

    /**
     * @covers \jackalope\transport\DavexClient::getDomFromBackend
     * @expectedException \PHPCR\NodeType\NoSuchNodeTypeException
     */
    public function testGetDomFromBackendNoSuchNodeType() {
        $t = $this->getTransportMock('testuri', array('getDomFromBackend', 'prepareRequest'));
        $t->curl = $this->getCurlFixture('fixtures/exceptionNoSuchNodeType.xml');
        $t->getDomFromBackend('POST', 'hulla');
    }

    /**
     * @covers \jackalope\transport\DavexClient::getDomFromBackend
     * @expectedException \PHPCR\ItemNotFoundException
     */
    public function testGetDomFromBackendItemNotFoundException() {
        $t = $this->getTransportMock('testuri', array('getDomFromBackend', 'prepareRequest'));
        $t->curl = $this->getCurlFixture('fixtures/exceptionItemNotFound.xml');
        $t->getDomFromBackend('POST', 'hulla');
    }

    /**
     * @covers \jackalope\transport\DavexClient::getDomFromBackend
     * @expectedException \PHPCR\RepositoryException
     */
    public function testGetDomFromBackendRepositoryException() {
        $t = $this->getTransportMock('testuri', array('getDomFromBackend', 'prepareRequest'));
        $t->curl = $this->getCurlFixture('fixtures/exceptionRepository.xml');
        $t->getDomFromBackend('POST', 'hulla');
    }

    /**
     * @covers \jackalope\transport\DavexClient::buildReportRequest
     */
    public function testBuildReportRequest() {
        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8"?><foo xmlns:dcr="http://www.day.com/jcr/webdav/1.0"/>',
            \jackalope\tests\ImplementationHelpers\DavexClient_Mock::buildReportRequestMock('foo')
        );
    }

    /**
     * @covers \jackalope\transport\DavexClient::getRepositoryDescriptors
     * @expectedException \PHPCR\RepositoryException
     */
    public function testGetRepositoryDescriptorsEmptyBackendResponse() {
        $dom = new DOMDocument();
        $dom->load('fixtures/empty.xml');
        $t = $this->getTransportMock();
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->will($this->returnValue($dom));
        $desc = $t->getRepositoryDescriptors();
    }

    /**
     * @covers \jackalope\transport\DavexClient::getRepositoryDescriptors
     */
    public function testGetRepositoryDescriptors() {
        $reportRequest = \jackalope\tests\ImplementationHelpers\DavexClient_Mock::buildReportRequestMock('dcr:repositorydescriptors');
        $dom = new DOMDocument();
        $dom->load('fixtures/repositoryDescriptors.xml');
        $t = $this->getTransportMock();
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->with(DavexClient_Mock::REPORT, 'testuri/', $reportRequest)
            ->will($this->returnValue($dom));

        $desc = $t->getRepositoryDescriptors();
        $this->assertType('array', $desc);
        $this->assertType('string', $desc['identifier.stability']);
        $this->assertEquals('identifier.stability.indefinite.duration', $desc['identifier.stability']);
        $this->assertType('array', $desc['node.type.management.property.types']);
        $this->assertType('string', $desc['node.type.management.property.types'][0]);
        $this->assertEquals('2', $desc['node.type.management.property.types'][0]);
    }

    /**
     * @covers \jackalope\transport\DavexClient::checkLogin
     * @expectedException \PHPCR\RepositoryException
     */
    public function testCheckLoginFail() {
        $t = new \jackalope\transport\DavexClient('http://localhost:1/server');
        $t->getNodeTypes();
    }

    /**
     * @covers \jackalope\transport\DavexClient::checkLogin
     */
    public function testCheckLogin() {
        $t = new \jackalope\tests\ImplementationHelpers\DavexClient_Mock('http://localhost:1/server');
        $t->workspaceUri = 'testuri';
        $t->checkLogin();
    }

    /**
     * @covers \jackalope\transport\DavexClient::getRepositoryDescriptors
     * @expectedException \PHPCR\RepositoryException
     */
    public function testGetRepositoryDescriptorsNoserver() {
        $t = new \jackalope\transport\DavexClient('http://localhost:1/server');
        $d = $t->getRepositoryDescriptors();
    }

    /**
     * @covers \jackalope\transport\DavexClient::buildPropfindRequest
     */
    public function testBuildPropfindRequestSingle() {
        $xmlStr = '<?xml version="1.0" encoding="UTF-8"?><D:propfind xmlns:D="DAV:" xmlns:dcr="http://www.day.com/jcr/webdav/1.0"><D:prop>';
        $xmlStr .= '<foo/>';
        $xmlStr .= '</D:prop></D:propfind>';
        $this->assertEquals($xmlStr, \jackalope\tests\ImplementationHelpers\DavexClient_Mock::buildPropfindRequestMock('foo'));
    }

    /**
     * @covers \jackalope\transport\DavexClient::buildPropfindRequest
     */
    public function testBuildPropfindRequestArray() {
        $xmlStr = '<?xml version="1.0" encoding="UTF-8"?><D:propfind xmlns:D="DAV:" xmlns:dcr="http://www.day.com/jcr/webdav/1.0"><D:prop>';
        $xmlStr .= '<foo/><bar/>';
        $xmlStr .= '</D:prop></D:propfind>';
        $this->assertEquals($xmlStr, \jackalope\tests\ImplementationHelpers\DavexClient_Mock::buildPropfindRequestMock(array('foo', 'bar')));
    }

    /**
     * @covers \jackalope\transport\DavexClient::login
     * @expectedException \PHPCR\RepositoryException
     */
    public function testLoginAlreadyLoggedin() {
        $t = $this->getTransportMock();
        $t->setCredentials('test');
        $t->login($this->credentials, $this->config['workspace']);
    }

    /**
     * @covers \jackalope\transport\DavexClient::login
     * @expectedException \PHPCR\LoginException
     */
    public function testLoginUnsportedCredentials() {
        $t = $this->getTransportMock();
        $t->login(new falseCredentialsMock(), $this->config['workspace']);
    }

    /**
     * @covers \jackalope\transport\DavexClient::login
     * @expectedException \PHPCR\RepositoryException
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
     * @covers \jackalope\transport\DavexClient::login
     * @expectedException \PHPCR\RepositoryException
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
     * @covers \jackalope\transport\DavexClient::login
     */
    public function testLogin() {
        $propfindRequest = \jackalope\tests\ImplementationHelpers\DavexClient_Mock::buildPropfindRequestMock(array('D:workspace', 'dcr:workspaceName'));
        $dom = new DOMDocument();
        $dom->load('fixtures/loginResponse.xml');
        $t = $this->getTransportMock();
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->with(\jackalope\transport\DavexClient::PROPFIND, 'testuri/tests', $propfindRequest)
            ->will($this->returnValue($dom));

        $x = $t->login($this->credentials, 'tests');
        $this->assertTrue($x);
        $this->assertEquals('tests', $t->workspace);
        $this->assertEquals('testuri/tests/jcr%3aroot', $t->workspaceUriRoot);

    }

    /**
     * @covers \jackalope\transport\DavexClient::login
     * @expectedException \PHPCR\NoSuchWorkspaceException
     */
    public function testLoginNoServer() {
        $t = new \jackalope\transport\DavexClient('http://localhost:1/server');
        $t->login($this->credentials, $this->config['workspace']);
    }

    /**
     * @covers \jackalope\transport\DavexClient::login
     * @expectedException \PHPCR\NoSuchWorkspaceException
     */
    public function testLoginNoSuchWorkspace() {
        $t = new \jackalope\transport\DavexClient($this->config['url']);
        $t->login($this->credentials, 'not-an-existing-workspace');
    }

    /**
     * @covers \jackalope\transport\DavexClient::getItem
     * @expectedException \PHPCR\RepositoryException
     */
    public function testGetItemWithoutAbsPath() {
        $t = $this->getTransportMock();
        $t->getItem('foo');
    }

    /**
     * @covers \jackalope\transport\DavexClient::getItem
     */
    public function testGetItem() {
        $t = $this->getTransportMock($this->config['url']);
        $t->expects($this->once())
            ->method('getJsonFromBackend')
            ->with(\jackalope\transport\DavexClient::GET, 'testWorkspaceUriRoot/foobar.0.json');

        $json = $t->getItem('/foobar');
    }

    /**
     * @covers \jackalope\transport\DavexClient::buildLocateRequest
     */
    public function testBuildLocateRequestMock() {
        $xmlstr = '<?xml version="1.0" encoding="UTF-8"?><dcr:locate-by-uuid xmlns:dcr="http://www.day.com/jcr/webdav/1.0"><D:href xmlns:D="DAV:">test</D:href></dcr:locate-by-uuid>';
        $this->assertEquals($xmlstr, \jackalope\tests\ImplementationHelpers\DavexClient_Mock::buildLocateRequestMock('test'));
    }

    /**
     * @covers \jackalope\transport\DavexClient::getNodePathForIdentifier
     * @expectedException \PHPCR\RepositoryException
     */
    public function testGetNodePathForIdentifierEmptyResponse() {
        $dom = new DOMDocument();
        $dom->load('fixtures/empty.xml');

        $t = $this->getTransportMock('testuri');
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->will($this->returnValue($dom));
        $t->getNodePathForIdentifier('test');
    }

    /**
     * @covers \jackalope\transport\DavexClient::getNodePathForIdentifier
     * @expectedException \PHPCR\RepositoryException
     */
    public function testGetNodePathForIdentifierWrongWorkspace() {
        $locateRequest = \jackalope\tests\ImplementationHelpers\DavexClient_Mock::buildLocateRequestMock('test');
        $dom = new DOMDocument();
        $dom->load('fixtures/locateRequest.xml');

        $t = $this->getTransportMock('testuri');
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->with(\jackalope\transport\DavexClient::REPORT, 'testWorkspaceUri', $locateRequest)
            ->will($this->returnValue($dom));
        $t->getNodePathForIdentifier('test');
    }

    /**
     * @covers \jackalope\transport\DavexClient::getNodePathForIdentifier
     */
    public function testGetNodePathForIdentifier() {
        $locateRequest = \jackalope\tests\ImplementationHelpers\DavexClient_Mock::buildLocateRequestMock('test');
        $dom = new DOMDocument();
        $dom->load('fixtures/locateRequestTests.xml');

        $t = $this->getTransportMock('testuri');
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->with(\jackalope\transport\DavexClient::REPORT, 'testWorkspaceUri', $locateRequest)
            ->will($this->returnValue($dom));
        $this->assertEquals('/tests_level1_access_base/idExample', $t->getNodePathForIdentifier('test'));
    }

    /**
     * @covers \jackalope\transport\DavexClient::getNamespaces
     * @expectedException \PHPCR\RepositoryException
     */
    public function testGetNamespacesEmptyResponse() {
        $dom = new DOMDocument();
        $dom->load('fixtures/empty.xml');

        $t = $this->getTransportMock($this->config['url']);
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->will($this->returnValue($dom));

        $ns = $t->getNamespaces();
    }

    /**
     * @covers \jackalope\transport\DavexClient::getNamespaces
     */
    public function testGetNamespaces() {
        $reportRequest = \jackalope\tests\ImplementationHelpers\DavexClient_Mock::buildReportRequestMock('dcr:registerednamespaces');
        $dom = new DOMDocument();
        $dom->load('fixtures/registeredNamespaces.xml');

        $t = $this->getTransportMock($this->config['url']);
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->with(\jackalope\transport\DavexClient::REPORT, 'testWorkspaceUri', $reportRequest)
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

        $requestStr = \jackalope\tests\ImplementationHelpers\DavexClient_Mock::buildNodeTypesRequestMock($params);

        $t = $this->getTransportMock();
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->with(\jackalope\transport\DavexClient::REPORT, 'testWorkspaceUri/jcr:root', $requestStr)
            ->will($this->returnValue($dom));
        return $t;
    }

    /**
     * @covers \jackalope\transport\DavexClient::buildNodeTypesRequest
     */
    public function testGetAllNodeTypesRequest() {
        $xmlStr = '<?xml version="1.0" encoding="utf-8" ?><jcr:nodetypes xmlns:jcr="http://www.day.com/jcr/webdav/1.0"><jcr:all-nodetypes/></jcr:nodetypes>';
        $this->assertEquals($xmlStr, \jackalope\tests\ImplementationHelpers\DavexClient_Mock::buildNodeTypesRequestMock(array()));
    }

    /**
     * @covers \jackalope\transport\DavexClient::buildNodeTypesRequest
     */
    public function testSpecificNodeTypesRequest() {
        $xmlStr= '<?xml version="1.0" encoding="utf-8" ?><jcr:nodetypes xmlns:jcr="http://www.day.com/jcr/webdav/1.0"><jcr:nodetype><jcr:nodetypename>foo</jcr:nodetypename></jcr:nodetype><jcr:nodetype><jcr:nodetypename>bar</jcr:nodetypename></jcr:nodetype><jcr:nodetype><jcr:nodetypename>foobar</jcr:nodetypename></jcr:nodetype></jcr:nodetypes>';
        $this->assertEquals($xmlStr, \jackalope\tests\ImplementationHelpers\DavexClient_Mock::buildNodeTypesRequestMock(array('foo', 'bar', 'foobar')));
    }

    /**
     * @covers \jackalope\transport\DavexClient::getNodeTypes
     */
    public function testGetNodeTypes() {
        $t = $this->setUpNodeTypeMock(array(), 'fixtures/nodetypes.xml');

        $nt = $t->getNodeTypes();
        $this->assertTrue($nt instanceOf DOMDocument);
        $this->assertEquals('mix:created', $nt->firstChild->firstChild->getAttribute('name'));
    }

    /**
     * @covers \jackalope\transport\DavexClient::getNodeTypes
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
     * @covers \jackalope\transport\DavexClient::getNodeTypes
     */
    public function testEmptyGetNodeTypes() {
        $t = $this->setUpNodeTypeMock(array(), 'fixtures/empty.xml');

        $this->setExpectedException('\PHPCR\RepositoryException');
        $nt = $t->getNodeTypes();
    }

    /** END TESTING NODE TYPES **/

    /**
     * @covers \jackalope\transport\DavexClient::getAccessibleWorkspaceNames
     */
    public function testGetAccessibleWorkspaceNames() {
        $dom = new DOMDocument();
        $dom->load('fixtures/accessibleWorkspaces.xml');

        $t = $this->getTransportMock('testuri');
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->with('PROPFIND', 'testuri/', \jackalope\tests\ImplementationHelpers\DavexClient_Mock::buildPropfindRequestMock(array('D:workspace')), 1)
            ->will($this->returnValue($dom));

        $names = $t->getAccessibleWorkspaceNames();
        $this->assertEquals(array('default', 'tests', 'security'), $names);
    }
}

class falseCredentialsMock implements \PHPCR\CredentialsInterface {

}
