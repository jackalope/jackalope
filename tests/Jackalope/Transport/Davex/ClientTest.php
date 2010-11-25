<?php

namespace Jackalope\Transport\Davex;

use Jackalope\TestCase;

use DOMDocument;
use DOMXPath;

class ClientTest extends TestCase
{
    public function getTransportMock($args = 'testuri', $changeMethods = array())
    {
        //Array XOR
        $defaultMockMethods = array('getDomFromBackend', 'getJsonFromBackend', 'checkLogin', 'initConnection', '__destruct', '__construct');
        $mockMethods = array_merge(array_diff($defaultMockMethods, $changeMethods), array_diff($changeMethods, $defaultMockMethods));
        return $this->getMock(
            __NAMESPACE__.'\ClientMock',
            $mockMethods,
            array($args)
        );
    }

    public function getCurlFixture($fixture = null, $errno = null)
    {
        $curl =  $this->getMock('Jackalope\Transport\curl');
        if (isset($fixture)) {
            if (is_file($fixture)) {
                $fixture = file_get_contents($fixture);
            }
            $curl
                ->expects($this->any())
                ->method('exec')
                ->will($this->returnValue($fixture));

            $curl
                ->expects($this->any())
                ->method('getinfo')
                ->with($this->equalTo(CURLINFO_HTTP_CODE))
                ->will($this->returnValue($HTTPResponseCode));
        }

        if (isset($errno)) {
            $curl
                ->expects($this->any())
                ->method('errno')
                ->will($this->returnValue($errno));
        }
        return $curl;
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::__construct
     */
    public function testConstructor()
    {
        $transport = new ClientMock('testuri');
        $this->assertSame('testuri/', $transport->server);
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::__destruct
     */
    public function testDestructor()
    {
        $transport = new ClientMock('testuri');
        $transport->__destruct();
        $this->assertSame(null, $transport->curl);
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::initConnection
     */
    public function testInitConnectionAlreadInitialized()
    {
        $t = $this->getMock(
            __NAMESPACE__.'\ClientMock',
            array('__destruct', '__construct'),
            array('testuri')
        );
        $t->curl = 'test';
        $this->assertFalse($t->initConnection());
        $this->assertSame('test', $t->curl);
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::initConnection
     */
    public function testInitConnection()
    {
        $t = $this->getMock(
            __NAMESPACE__.'\ClientMock',
            array('__destruct', '__construct'),
            array('testuri')
        );
        $t->initConnection();
        $this->assertType('Jackalope\Transport\curl', $t->curl);
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::closeConnection
     */
    public function testCloseConnectionAlreadyClosed()
    {
        $t = $this->getTransportMock();
        $t->curl = null;
        $this->assertFalse($t->closeConnection());
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::closeConnection
     */
    public function testCloseConnection()
    {
        $t = $this->getTransportMock();
        $t->curl = $this->getCurlFixture();
        $t->curl->expects($this->once())
            ->method('close');
        $t->closeConnection();
        $this->assertSame(null, $t->curl);
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::prepareRequest
     */
    public function testPrepareRequest()
    {
        $t = $this->getTransportMock();
        $t->curl = $this->getMock('Jackalope\Transport\curl', array());
        $t->curl->expects($this->at(0))
            ->method('setopt')
            ->with(CURLOPT_CUSTOMREQUEST, 'testmethod');
        $t->curl->expects($this->at(1))
            ->method('setopt')
            ->with(CURLOPT_URL, 'testuri');
        $t->curl->expects($this->at(2))
            ->method('setopt')
            ->with(CURLOPT_HTTPHEADER, array(
                'Depth: 3',
                'Content-Type: text/xml; charset=utf-8',
                'User-Agent: '. \Jackalope\Transport\Davex\Request::USER_AGENT
            ));
        $t->curl->expects($this->at(3))
            ->method('setopt')
            ->with(CURLOPT_POSTFIELDS, 'testbody');
        $t->prepareRequest('testmethod', 'testuri', 'testbody', 3);
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::prepareRequest
     */
    public function testPrepareRequestWithCredentials()
    {
        $t = $this->getTransportMock();
        $t->setCredentials(new \PHPCR\SimpleCredentials('foo', 'bar'));
        $t->curl = $this->getMock('Jackalope\Transport\curl', array());
        $t->curl->expects($this->at(0))
            ->method('setopt')
            ->with(CURLOPT_USERPWD, 'foo:bar');
        $t->curl->expects($this->at(1))
            ->method('setopt')
            ->with(CURLOPT_CUSTOMREQUEST, 'testmethod');
        $t->prepareRequest('testmethod', 'testuri', 'testbody', 3);
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getRawFromBackend
     */
    public function testGetRawFromBackend()
    {
        $t = $this->getTransportMock();
        $t->curl = $this->getCurlFixture('hulla hulla');
        $this->assertSame('hulla hulla', $t->getRawFromBackend());
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getRawFromBackend
     * @expectedException \PHPCR\NoSuchWorkspaceException
     */
    public function testGetRawFromBackendNoHost()
    {
        $t = $this->getTransportMock();
        $t->curl = $this->getCurlFixture(null, CURLE_COULDNT_RESOLVE_HOST);
        $t->getRawFromBackend();
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getRawFromBackend
     * @expectedException \PHPCR\NoSuchWorkspaceException
     */
    public function testGetRawFromBackendNoConnect()
    {
        $t = $this->getTransportMock();
        $t->curl = $this->getCurlFixture(null, CURLE_COULDNT_CONNECT);
        $t->getRawFromBackend();
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getRawFromBackend
     * @expectedException \PHPCR\RepositoryException
     */
    public function testGetRawFromBackendNoData()
    {
        $t = $this->getTransportMock();
        $t->curl = $this->getCurlFixture(null);
        $t->getRawFromBackend();
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getJsonFromBackend
     */
    public function testGetJsonFromBackend()
    {
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
     * @covers \Jackalope\Transport\Davex\Client::getJsonFromBackend
     * @expectedException \PHPCR\ItemNotFoundException
     */
    public function testGetJsonFromBackendItemNotFound()
    {
        $t = $this->getTransportMock('testuri', array('getJsonFromBackend', 'prepareRequest'));
        $t->curl = $this->getCurlFixture('fixtures/empty.xml', null, 404);
//        $t->curl->expects($this->any())
//            ->method('getinfo')
//            ->with(CURLINFO_HTTP_CODE)
//            ->will($this->returnValue(404));
        $t->expects($this->once())
            ->method('prepareRequest')
            ->with('POST', 'hulla', '', 0);
        $t->getJsonFromBackend('POST', 'hulla');
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getJsonFromBackend
     * @expectedException \PHPCR\RepositoryException
     */
    public function testGetJsonFromBackendRepositoryException()
    {
        $t = $this->getTransportMock('testuri', array('getJsonFromBackend', 'prepareRequest'));
        $t->curl = $this->getCurlFixture('fixtures/empty.xml');
        $t->curl->expects($this->any())
            ->method('getinfo')
            ->will($this->returnValue(array('http_code' => 500)));
        $t->getJsonFromBackend('POST', 'hulla');
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getJsonFromBackend
     * @expectedException \PHPCR\RepositoryException
     */
    public function testGetJsonFromBackendInvalidJson()
    {
        $t = $this->getTransportMock('testuri', array('getJsonFromBackend', 'prepareRequest'));
        $t->curl = $this->getCurlFixture('invalid json');
        $t->getJsonFromBackend('POST', 'hulla');
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getDomFromBackend
     */
    public function testGetDomFromBackend()
    {
        $t = $this->getTransportMock('testuri', array('getDomFromBackend', 'prepareRequest'));
        $t->curl = $this->getCurlFixture('fixtures/empty.xml');
        $t->expects($this->once())
            ->method('prepareRequest')
            ->with('GET', 'foo', 'bar', 1);
        $dom = $t->getDomFromBackend('GET', 'foo', 'bar', 1);
        $this->assertXmlStringEqualsXmlFile('fixtures/empty.xml', $dom->saveXML());
    }


    /**
     * @covers \Jackalope\Transport\Davex\Client::getDomFromBackend
     * @expectedException \PHPCR\NoSuchWorkspaceException
     */
    public function testGetDomFromBackendNoWorkspace()
    {
        $t = $this->getTransportMock('testuri', array('getDomFromBackend', 'prepareRequest'));
        $t->curl = $this->getCurlFixture('fixtures/exceptionNoWorkspace.xml');
        $t->expects($this->once())
            ->method('prepareRequest')
            ->with('POST', 'hulla', '', 0);
        $t->getDomFromBackend('POST', 'hulla');
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getDomFromBackend
     * @expectedException \PHPCR\NodeType\NoSuchNodeTypeException
     */
    public function testGetDomFromBackendNoSuchNodeType()
    {
        $t = $this->getTransportMock('testuri', array('getDomFromBackend', 'prepareRequest'));
        $t->curl = $this->getCurlFixture('fixtures/exceptionNoSuchNodeType.xml');
        $t->getDomFromBackend('POST', 'hulla');
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getDomFromBackend
     * @expectedException \PHPCR\ItemNotFoundException
     */
    public function testGetDomFromBackendItemNotFoundException()
    {
        $t = $this->getTransportMock('testuri', array('getDomFromBackend', 'prepareRequest'));
        $t->curl = $this->getCurlFixture('fixtures/exceptionItemNotFound.xml');
        $t->getDomFromBackend('POST', 'hulla');
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getDomFromBackend
     * @expectedException \PHPCR\RepositoryException
     */
    public function testGetDomFromBackendRepositoryException()
    {
        $t = $this->getTransportMock('testuri', array('getDomFromBackend', 'prepareRequest'));
        $t->curl = $this->getCurlFixture('fixtures/exceptionRepository.xml');
        $t->getDomFromBackend('POST', 'hulla');
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::buildReportRequest
     */
    public function testBuildReportRequest()
    {
        $this->assertSame(
            '<?xml version="1.0" encoding="UTF-8"?><foo xmlns:dcr="http://www.day.com/jcr/webdav/1.0"/>',
            ClientMock::buildReportRequestMock('foo')
        );
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getRepositoryDescriptors
     * @expectedException \PHPCR\RepositoryException
     */
    public function testGetRepositoryDescriptorsEmptyBackendResponse()
    {
        $dom = new DOMDocument();
        $dom->load('fixtures/empty.xml');
        $t = $this->getTransportMock();
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->will($this->returnValue($dom));
        $desc = $t->getRepositoryDescriptors();
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getRepositoryDescriptors
     */
    public function testGetRepositoryDescriptors()
    {
        $reportRequest = ClientMock::buildReportRequestMock('dcr:repositorydescriptors');
        $dom = new DOMDocument();
        $dom->load('fixtures/repositoryDescriptors.xml');
        $t = $this->getTransportMock();
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->with(ClientMock::REPORT, 'testuri/', $reportRequest)
            ->will($this->returnValue($dom));

        $desc = $t->getRepositoryDescriptors();
        $this->assertType('array', $desc);
        $this->assertType('string', $desc['identifier.stability']);
        $this->assertSame('identifier.stability.indefinite.duration', $desc['identifier.stability']);
        $this->assertType('array', $desc['node.type.management.property.types']);
        $this->assertType('string', $desc['node.type.management.property.types'][0]);
        $this->assertSame('2', $desc['node.type.management.property.types'][0]);
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::checkLogin
     * @expectedException \PHPCR\RepositoryException
     */
    public function testCheckLoginFail()
    {
        $t = new ClientMock('http://localhost:1/server');
        $t->getNodeTypes();
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::checkLogin
     */
    public function testCheckLogin()
    {
        $t = new ClientMock('http://localhost:1/server');
        $t->workspaceUri = 'testuri';
        $t->checkLogin();
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getRepositoryDescriptors
     * @expectedException \PHPCR\RepositoryException
     */
    public function testGetRepositoryDescriptorsNoserver()
    {
        $t = new \Jackalope\Transport\Davex\Client('http://localhost:1/server');
        $d = $t->getRepositoryDescriptors();
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::buildPropfindRequest
     */
    public function testBuildPropfindRequestSingle()
    {
        $xmlStr = '<?xml version="1.0" encoding="UTF-8"?><D:propfind xmlns:D="DAV:" xmlns:dcr="http://www.day.com/jcr/webdav/1.0"><D:prop>';
        $xmlStr .= '<foo/>';
        $xmlStr .= '</D:prop></D:propfind>';
        $this->assertSame($xmlStr, ClientMock::buildPropfindRequestMock('foo'));
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::buildPropfindRequest
     */
    public function testBuildPropfindRequestArray()
    {
        $xmlStr = '<?xml version="1.0" encoding="UTF-8"?><D:propfind xmlns:D="DAV:" xmlns:dcr="http://www.day.com/jcr/webdav/1.0"><D:prop>';
        $xmlStr .= '<foo/><bar/>';
        $xmlStr .= '</D:prop></D:propfind>';
        $this->assertSame($xmlStr, ClientMock::buildPropfindRequestMock(array('foo', 'bar')));
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::login
     * @expectedException \PHPCR\RepositoryException
     */
    public function testLoginAlreadyLoggedin()
    {
        $t = $this->getTransportMock();
        $t->setCredentials('test');
        $t->login($this->credentials, $this->config['workspace']);
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::login
     * @expectedException \PHPCR\LoginException
     */
    public function testLoginUnsportedCredentials()
    {
        $t = $this->getTransportMock();
        $t->login(new falseCredentialsMock(), $this->config['workspace']);
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::login
     * @expectedException \PHPCR\RepositoryException
     */
    public function testLoginEmptyBackendResponse()
    {
        $dom = new DOMDocument();
        $dom->load('fixtures/empty.xml');
        $t = $this->getTransportMock();
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->will($this->returnValue($dom));
        $t->login($this->credentials, $this->config['workspace']);
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::login
     * @expectedException \PHPCR\RepositoryException
     */
    public function testLoginWrongWorkspace()
    {
        $dom = new DOMDocument();
        $dom->load('fixtures/wrongWorkspace.xml');
        $t = $this->getTransportMock();
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->will($this->returnValue($dom));
        $t->login($this->credentials, $this->config['workspace']);
    }

     /**
     * @covers \Jackalope\Transport\Davex\Client::login
     */
    public function testLogin()
    {
        $propfindRequest = ClientMock::buildPropfindRequestMock(array('D:workspace', 'dcr:workspaceName'));
        $dom = new DOMDocument();
        $dom->load('fixtures/loginResponse.xml');
        $t = $this->getTransportMock();
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->with(\Jackalope\Transport\Davex\Client::PROPFIND, 'testuri/tests', $propfindRequest)
            ->will($this->returnValue($dom));

        $x = $t->login($this->credentials, 'tests');
        $this->assertTrue($x);
        $this->assertSame('tests', $t->workspace);
        $this->assertSame('testuri/tests/jcr%3aroot', $t->workspaceUriRoot);

    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::login
     * @expectedException \PHPCR\NoSuchWorkspaceException
     */
    public function testLoginNoServer()
    {
        $t = new \Jackalope\Transport\Davex\Client('http://localhost:1/server');
        $t->login($this->credentials, $this->config['workspace']);
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::login
     * @expectedException \PHPCR\NoSuchWorkspaceException
     */
    public function testLoginNoSuchWorkspace()
    {
        $t = new \Jackalope\Transport\Davex\Client($this->config['url']);
        $t->login($this->credentials, 'not-an-existing-workspace');
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getItem
     * @expectedException \PHPCR\RepositoryException
     */
    public function testGetItemWithoutAbsPath()
    {
        $t = $this->getTransportMock();
        $t->getItem('foo');
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getItem
     */
    public function testGetItem()
    {
        $t = $this->getTransportMock($this->config['url']);
        $t->expects($this->once())
            ->method('getJsonFromBackend')
            ->with(\Jackalope\Transport\Davex\Client::GET, 'testWorkspaceUriRoot/foobar.0.json');

        $json = $t->getItem('/foobar');
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::buildLocateRequest
     */
    public function testBuildLocateRequestMock()
    {
        $xmlstr = '<?xml version="1.0" encoding="UTF-8"?><dcr:locate-by-uuid xmlns:dcr="http://www.day.com/jcr/webdav/1.0"><D:href xmlns:D="DAV:">test</D:href></dcr:locate-by-uuid>';
        $this->assertSame($xmlstr, ClientMock::buildLocateRequestMock('test'));
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getNodePathForIdentifier
     * @expectedException \PHPCR\RepositoryException
     */
    public function testGetNodePathForIdentifierEmptyResponse()
    {
        $dom = new DOMDocument();
        $dom->load('fixtures/empty.xml');

        $t = $this->getTransportMock('testuri');
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->will($this->returnValue($dom));
        $t->getNodePathForIdentifier('test');
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getNodePathForIdentifier
     * @expectedException \PHPCR\RepositoryException
     */
    public function testGetNodePathForIdentifierWrongWorkspace()
    {
        $locateRequest = ClientMock::buildLocateRequestMock('test');
        $dom = new DOMDocument();
        $dom->load('fixtures/locateRequest.xml');

        $t = $this->getTransportMock('testuri');
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->with(\Jackalope\Transport\Davex\Client::REPORT, 'testWorkspaceUri', $locateRequest)
            ->will($this->returnValue($dom));
        $t->getNodePathForIdentifier('test');
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getNodePathForIdentifier
     */
    public function testGetNodePathForIdentifier()
    {
        $locateRequest = ClientMock::buildLocateRequestMock('test');
        $dom = new DOMDocument();
        $dom->load('fixtures/locateRequestTests.xml');

        $t = $this->getTransportMock('testuri');
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->with(\Jackalope\Transport\Davex\Client::REPORT, 'testWorkspaceUri', $locateRequest)
            ->will($this->returnValue($dom));
        $this->assertSame('/tests_level1_access_base/idExample', $t->getNodePathForIdentifier('test'));
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getNamespaces
     * @expectedException \PHPCR\RepositoryException
     */
    public function testGetNamespacesEmptyResponse()
    {
        $dom = new DOMDocument();
        $dom->load('fixtures/empty.xml');

        $t = $this->getTransportMock($this->config['url']);
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->will($this->returnValue($dom));

        $ns = $t->getNamespaces();
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getNamespaces
     */
    public function testGetNamespaces()
    {
        $reportRequest = ClientMock::buildReportRequestMock('dcr:registerednamespaces');
        $dom = new DOMDocument();
        $dom->load('fixtures/registeredNamespaces.xml');

        $t = $this->getTransportMock($this->config['url']);
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->with(\Jackalope\Transport\Davex\Client::REPORT, 'testWorkspaceUri', $reportRequest)
            ->will($this->returnValue($dom));

        $ns = $t->getNamespaces();
        $this->assertType('array', $ns);
        foreach($ns as $prefix => $uri) {
            $this->assertType('string', $prefix);
            $this->assertType('string', $uri);
        }
    }

    /** START TESTING NODE TYPES **/
    protected function setUpNodeTypeMock($params, $fixture)
    {
        $dom = new DOMDocument();
        $dom->load($fixture);

        $requestStr = ClientMock::buildNodeTypesRequestMock($params);

        $t = $this->getTransportMock();
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->with(\Jackalope\Transport\Davex\Client::REPORT, 'testWorkspaceUri/jcr:root', $requestStr)
            ->will($this->returnValue($dom));
        return $t;
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::buildNodeTypesRequest
     */
    public function testGetAllNodeTypesRequest()
    {
        $xmlStr = '<?xml version="1.0" encoding="utf-8" ?><jcr:nodetypes xmlns:jcr="http://www.day.com/jcr/webdav/1.0"><jcr:all-nodetypes/></jcr:nodetypes>';
        $this->assertSame($xmlStr, ClientMock::buildNodeTypesRequestMock(array()));
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::buildNodeTypesRequest
     */
    public function testSpecificNodeTypesRequest()
    {
        $xmlStr= '<?xml version="1.0" encoding="utf-8" ?><jcr:nodetypes xmlns:jcr="http://www.day.com/jcr/webdav/1.0"><jcr:nodetype><jcr:nodetypename>foo</jcr:nodetypename></jcr:nodetype><jcr:nodetype><jcr:nodetypename>bar</jcr:nodetypename></jcr:nodetype><jcr:nodetype><jcr:nodetypename>foobar</jcr:nodetypename></jcr:nodetype></jcr:nodetypes>';
        $this->assertSame($xmlStr, ClientMock::buildNodeTypesRequestMock(array('foo', 'bar', 'foobar')));
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getNodeTypes
     */
    public function testGetNodeTypes()
    {
        $t = $this->setUpNodeTypeMock(array(), 'fixtures/nodetypes.xml');

        $nt = $t->getNodeTypes();
        $this->assertTrue($nt instanceOf DOMDocument);
        $this->assertSame('mix:created', $nt->firstChild->firstChild->getAttribute('name'));
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getNodeTypes
     */
    public function testSpecificGetNodeTypes()
    {
        $t = $this->setUpNodeTypeMock(array('nt:folder', 'nt:file'), 'fixtures/small_nodetypes.xml');

        $nt = $t->getNodeTypes(array('nt:folder', 'nt:file'));
        $this->assertType('DOMDocument', $nt);
        $xp = new DOMXpath($nt);
        $res = $xp->query('//nodeType');
        $this->assertSame(2, $res->length);
        $this->assertSame('nt:folder', $res->item(0)->getAttribute('name'));
        $this->assertSame('nt:file', $res->item(1)->getAttribute('name'));
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getNodeTypes
     */
    public function testEmptyGetNodeTypes()
    {
        $t = $this->setUpNodeTypeMock(array(), 'fixtures/empty.xml');

        $this->setExpectedException('\PHPCR\RepositoryException');
        $nt = $t->getNodeTypes();
    }

    /** END TESTING NODE TYPES **/

    /**
     * @covers \Jackalope\Transport\Davex\Client::getAccessibleWorkspaceNames
     */
    public function testGetAccessibleWorkspaceNames()
    {
        $dom = new DOMDocument();
        $dom->load('fixtures/accessibleWorkspaces.xml');

        $t = $this->getTransportMock('testuri');
        $t->expects($this->once())
            ->method('getDomFromBackend')
            ->with('PROPFIND', 'testuri/', ClientMock::buildPropfindRequestMock(array('D:workspace')), 1)
            ->will($this->returnValue($dom));

        $names = $t->getAccessibleWorkspaceNames();
        $this->assertSame(array('default', 'tests', 'security'), $names);
    }
}

class falseCredentialsMock implements \PHPCR\CredentialsInterface
{
}

class ClientMock extends Client
{
    public $curl;
    public $server = 'testserver';
    public $workspace = 'testWorkspace';
    public $workspaceUri = 'testWorkspaceUri';
    public $workspaceUriRoot = 'testWorkspaceUriRoot';

    static public function buildNodeTypesRequestMock(Array $params)
    {
        return self::buildNodeTypesRequest($params);
    }

    static public function buildReportRequestMock($name = '')
    {
        return self::buildReportRequest($name);
    }

    static public function buildPropfindRequestMock($args = array())
    {
        return self::buildPropfindRequest($args);
    }

    static public function buildLocateRequestMock($arg = '')
    {
        return self::buildLocateRequest($arg);
    }

    public function initConnection()
    {
        return parent::initConnection();
    }

    public function closeConnection()
    {
        return parent::closeConnection();
    }

    public function prepareRequest($type, $uri, $body = '', $depth = 0, $contentType = 'text/xml; charset=utf-8')
    {
        return parent::prepareRequest($type, $uri, $body, $depth, $contentType);
    }

    public function setCredentials($credentials)
    {
        $this->credentials = $credentials;
    }

    public function checkLogin()
    {
        parent::checkLogin();
    }

    public function getRawFromBackend()
    {
        return parent::getRawFromBackend();
    }

    public function getDomFromBackend($type, $uri, $body='', $depth=0)
    {
        return parent::getDomFromBackend($type, $uri, $body, $depth);
    }

    public function getJsonFromBackend($type, $uri, $body='', $depth=0)
    {
        return parent::getJsonFromBackend($type, $uri, $body, $depth);
    }
}
