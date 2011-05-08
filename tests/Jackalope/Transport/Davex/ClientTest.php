<?php

namespace Jackalope\Transport\Davex;

use Jackalope\TestCase;

use DOMDocument;
use DOMXPath;

class ClientTest extends TestCase
{
    public function getTransportMock($args = 'testuri', $changeMethods = array())
    {
        $factory = new \Jackalope\Factory;
        //Array XOR
        $defaultMockMethods = array('getRequest', '__destruct', '__construct');
        $mockMethods = array_merge(array_diff($defaultMockMethods, $changeMethods), array_diff($changeMethods, $defaultMockMethods));
        return $this->getMock(
            __NAMESPACE__.'\ClientMock',
            $mockMethods,
            array($factory, $args)
        );
    }

    public function getRequestMock($response = '', $changeMethods = array(), $args = array(null, null, null))
    {
        $factory = new \Jackalope\Factory;
        array_unshift($args, $factory);
        $defaultMockMethods = array('execute', 'executeDom', 'executeJson');
        $mockMethods = array_merge(array_diff($defaultMockMethods, $changeMethods), array_diff($changeMethods, $defaultMockMethods));
        $request = $this->getMock('Jackalope\Transport\Davex\Request', $mockMethods, $args);

        $request
            ->expects($this->any())
            ->method('execute')
            ->will($this->returnValue($response));

        $request
            ->expects($this->any())
            ->method('executeDom')
            ->will($this->returnValue($response));

        $request
            ->expects($this->any())
            ->method('executeJson')
            ->will($this->returnValue($response));

        return $request;
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::__construct
     */
    public function testConstructor()
    {
        $factory = new \Jackalope\Factory;
        $transport = new ClientMock($factory, 'testuri');
        $this->assertSame('testuri/', $transport->server);
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::__destruct
     */
    public function testDestructor()
    {
        $factory = new \Jackalope\Factory;
        $transport = new ClientMock($factory, 'testuri');
        $transport->__destruct();
        $this->assertSame(null, $transport->curl);
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getRequest
     */
    public function testGetRequestDoesntReinitCurl()
    {
        $t = $this->getTransportMock();
        $t->curl = 'test';
        $t->getRequestMock('GET', '/foo');
        $this->assertSame('test', $t->curl);
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::buildReportRequest
     */
    public function testBuildReportRequest()
    {
        $this->assertSame(
            '<?xml version="1.0" encoding="UTF-8"?><foo xmlns:dcr="http://www.day.com/jcr/webdav/1.0"/>',
            $this->getTransportMock()->buildReportRequestMock('foo')
        );
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getRepositoryDescriptors
     * @expectedException \PHPCR\RepositoryException
     */
    public function testGetRepositoryDescriptorsEmptyBackendResponse()
    {
        $dom = new DOMDocument();
        $dom->load(dirname(__FILE__).'/../../../fixtures/empty.xml');
        $t = $this->getTransportMock();
        $request = $this->getRequestMock($dom, array('setBody'));
        $t->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));
        $desc = $t->getRepositoryDescriptors();
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getRepositoryDescriptors
     */
    public function testGetRepositoryDescriptors()
    {
        $reportRequest = $this->getTransportMock()->buildReportRequestMock('dcr:repositorydescriptors');
        $dom = new DOMDocument();
        $dom->load(dirname(__FILE__).'/../../../fixtures/repositoryDescriptors.xml');
        $t = $this->getTransportMock();
        $request = $this->getRequestMock($dom, array('setBody'));
        $t->expects($this->once())
            ->method('getRequest')
            ->with(Request::REPORT, 'testuri/')
            ->will($this->returnValue($request));
        $request->expects($this->once())
            ->method('setBody')
            ->with($reportRequest);

        $desc = $t->getRepositoryDescriptors();
        $this->assertType('array', $desc);
        $this->assertType('string', $desc['identifier.stability']);
        $this->assertSame('identifier.stability.indefinite.duration', $desc['identifier.stability']);
        $this->assertType('array', $desc['node.type.management.property.types']);
        $this->assertType('string', $desc['node.type.management.property.types'][0]);
        $this->assertSame('2', $desc['node.type.management.property.types'][0]);
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getRequest
     * @expectedException \PHPCR\RepositoryException
     */
    public function testExceptionIfNotLoggedIn()
    {
        $factory = new \Jackalope\Factory;
        $t = new ClientMock($factory, 'http://localhost:1/server');
        $t->getNodeTypes();
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getRepositoryDescriptors
     * @expectedException \PHPCR\RepositoryException
     */
    public function testGetRepositoryDescriptorsNoserver()
    {
        $factory = new \Jackalope\Factory;
        $t = new \Jackalope\Transport\Davex\Client($factory, 'http://localhost:1/server');
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
        $this->assertSame($xmlStr, $this->getTransportMock()->buildPropfindRequestMock('foo'));
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::buildPropfindRequest
     */
    public function testBuildPropfindRequestArray()
    {
        $xmlStr = '<?xml version="1.0" encoding="UTF-8"?><D:propfind xmlns:D="DAV:" xmlns:dcr="http://www.day.com/jcr/webdav/1.0"><D:prop>';
        $xmlStr .= '<foo/><bar/>';
        $xmlStr .= '</D:prop></D:propfind>';
        $this->assertSame($xmlStr, $this->getTransportMock()->buildPropfindRequestMock(array('foo', 'bar')));
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
        $dom->load(dirname(__FILE__).'/../../../fixtures/empty.xml');
        $t = $this->getTransportMock();
        $request = $this->getRequestMock($dom, array('setBody'));
        $t->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));
        $t->login($this->credentials, $this->config['workspace']);
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::login
     * @expectedException \PHPCR\RepositoryException
     */
    public function testLoginWrongWorkspace()
    {
        $dom = new DOMDocument();
        $dom->load(dirname(__FILE__).'/../../../fixtures/wrongWorkspace.xml');
        $t = $this->getTransportMock();
        $request = $this->getRequestMock($dom, array('setBody'));
        $t->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));
        $t->login($this->credentials, $this->config['workspace']);
    }

     /**
     * @covers \Jackalope\Transport\Davex\Client::login
     */
    public function testLogin()
    {
        $propfindRequest = $this->getTransportMock()->buildPropfindRequestMock(array('D:workspace', 'dcr:workspaceName'));
        $dom = new DOMDocument();
        $dom->load(dirname(__FILE__).'/../../../fixtures/loginResponse.xml');
        $t = $this->getTransportMock();

        $request = $this->getRequestMock($dom, array('setBody'));
        $t->expects($this->once())
            ->method('getRequest')
            ->with(Request::PROPFIND, 'testuri/tests')
            ->will($this->returnValue($request));

        $request->expects($this->once())
            ->method('setBody')
            ->with($propfindRequest);

        $x = $t->login($this->credentials, 'tests');
        $this->assertTrue($x);
        $this->assertSame('tests', $t->workspace);
        $this->assertSame('testuri/tests/jcr:root', $t->workspaceUriRoot);

    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::login
     * @expectedException \PHPCR\NoSuchWorkspaceException
     */
    public function testLoginNoServer()
    {
        $factory = new \Jackalope\Factory;
        $t = new \Jackalope\Transport\Davex\Client($factory, 'http://localhost:1/server');
        $t->login($this->credentials, $this->config['workspace']);
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::login
     * @expectedException \PHPCR\NoSuchWorkspaceException
     */
    public function testLoginNoSuchWorkspace()
    {
        $factory = new \Jackalope\Factory;
        $t = new \Jackalope\Transport\Davex\Client($factory, $this->config['url']);
        $t->login($this->credentials, 'not-an-existing-workspace');
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getNode
     * @expectedException \PHPCR\RepositoryException
     */
    public function testGetNodeWithoutAbsPath()
    {
        $t = $this->getTransportMock();
        $t->getNode('foo');
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getNode
     */
    public function testGetNode()
    {
        $t = $this->getTransportMock($this->config['url']);

        $request = $this->getRequestMock();
        $t->expects($this->once())
            ->method('getRequest')
            ->with(Request::GET, '/foobar.0.json')
            ->will($this->returnValue($request));

        $json = $t->getNode('/foobar');
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::buildLocateRequest
     */
    public function testBuildLocateRequestMock()
    {
        $xmlstr = '<?xml version="1.0" encoding="UTF-8"?><dcr:locate-by-uuid xmlns:dcr="http://www.day.com/jcr/webdav/1.0"><D:href xmlns:D="DAV:">test</D:href></dcr:locate-by-uuid>';
        $this->assertSame($xmlstr, $this->getTransportMock()->buildLocateRequestMock('test'));
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getNodePathForIdentifier
     * @expectedException \PHPCR\RepositoryException
     */
    public function testGetNodePathForIdentifierEmptyResponse()
    {
        $dom = new DOMDocument();
        $dom->load(dirname(__FILE__).'/../../../fixtures/empty.xml');

        $t = $this->getTransportMock('testuri');
        $request = $this->getRequestMock($dom, array('setBody'));
        $t->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));
        $t->getNodePathForIdentifier('test');
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getNodePathForIdentifier
     * @expectedException \PHPCR\RepositoryException
     */
    public function testGetNodePathForIdentifierWrongWorkspace()
    {
        $locateRequest = $this->getTransportMock()->buildLocateRequestMock('test');
        $dom = new DOMDocument();
        $dom->load(dirname(__FILE__).'/../../../fixtures/locateRequest.xml');

        $t = $this->getTransportMock('testuri');
        $request = $this->getRequestMock($dom, array('setBody'));
        $t->expects($this->once())
            ->method('getRequest')
            ->with(Request::REPORT, 'testWorkspaceUri')
            ->will($this->returnValue($request));
        $request->expects($this->once())
            ->method('setBody')
            ->with($locateRequest);
        $t->getNodePathForIdentifier('test');
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getNodePathForIdentifier
     */
    public function testGetNodePathForIdentifier()
    {
        $locateRequest = $this->getTransportMock()->buildLocateRequestMock('test');
        $dom = new DOMDocument();
        $dom->load(dirname(__FILE__).'/../../../fixtures/locateRequestTests.xml');

        $t = $this->getTransportMock('testuri');
        $request = $this->getRequestMock($dom, array('setBody'));
        $t->expects($this->once())
            ->method('getRequest')
            ->with(Request::REPORT, 'testWorkspaceUri')
            ->will($this->returnValue($request));
        $request->expects($this->once())
            ->method('setBody')
            ->with($locateRequest);
        $this->assertSame('/tests_level1_access_base/idExample', $t->getNodePathForIdentifier('test'));
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getNamespaces
     * @expectedException \PHPCR\RepositoryException
     */
    public function testGetNamespacesEmptyResponse()
    {
        $dom = new DOMDocument();
        $dom->load(dirname(__FILE__).'/../../../fixtures/empty.xml');

        $t = $this->getTransportMock($this->config['url']);
        $request = $this->getRequestMock($dom, array('setBody'));
        $t->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $ns = $t->getNamespaces();
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getNamespaces
     */
    public function testGetNamespaces()
    {
        $reportRequest = $this->getTransportMock()->buildReportRequestMock('dcr:registerednamespaces');
        $dom = new DOMDocument();
        $dom->load(dirname(__FILE__).'/../../../fixtures/registeredNamespaces.xml');

        $t = $this->getTransportMock($this->config['url']);
        $request = $this->getRequestMock($dom, array('setBody'));
        $t->expects($this->once())
            ->method('getRequest')
            ->with(Request::REPORT, 'testWorkspaceUri')
            ->will($this->returnValue($request));
        $request->expects($this->once())
            ->method('setBody')
            ->with($reportRequest);

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

        $requestStr = $this->getTransportMock()->buildNodeTypesRequestMock($params);

        $t = $this->getTransportMock();
        $request = $this->getRequestMock($dom, array('setBody'));
        $t->expects($this->once())
            ->method('getRequest')
            ->with(Request::REPORT, 'testWorkspaceUriRoot')
            ->will($this->returnValue($request));
        $request->expects($this->once())
            ->method('setBody')
            ->with($requestStr);
        return $t;
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::buildNodeTypesRequest
     */
    public function testGetAllNodeTypesRequest()
    {
        $xmlStr = '<?xml version="1.0" encoding="utf-8" ?><jcr:nodetypes xmlns:jcr="http://www.day.com/jcr/webdav/1.0"><jcr:all-nodetypes/></jcr:nodetypes>';
        $this->assertSame($xmlStr, $this->getTransportMock()->buildNodeTypesRequestMock(array()));
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::buildNodeTypesRequest
     */
    public function testSpecificNodeTypesRequest()
    {
        $xmlStr= '<?xml version="1.0" encoding="utf-8" ?><jcr:nodetypes xmlns:jcr="http://www.day.com/jcr/webdav/1.0"><jcr:nodetype><jcr:nodetypename>foo</jcr:nodetypename></jcr:nodetype><jcr:nodetype><jcr:nodetypename>bar</jcr:nodetypename></jcr:nodetype><jcr:nodetype><jcr:nodetypename>foobar</jcr:nodetypename></jcr:nodetype></jcr:nodetypes>';
        $this->assertSame($xmlStr, $this->getTransportMock()->buildNodeTypesRequestMock(array('foo', 'bar', 'foobar')));
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getNodeTypes
     */
    public function testGetNodeTypes()
    {
        $t = $this->setUpNodeTypeMock(array(), dirname(__FILE__).'/../../../fixtures/nodetypes.xml');

        $nt = $t->getNodeTypes();
        $this->assertType('array', $nt);
        $this->assertSame('mix:created', $nt[0]['name']);
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getNodeTypes
     */
    public function testSpecificGetNodeTypes()
    {
        $t = $this->setUpNodeTypeMock(array('nt:folder', 'nt:file'), dirname(__FILE__).'/../../../fixtures/small_nodetypes.xml');

        $nt = $t->getNodeTypes(array('nt:folder', 'nt:file'));
        $this->assertType('array', $nt);
        $this->assertSame(2, count($nt));
        $this->assertSame('nt:folder', $nt[0]['name']);
        $this->assertSame('nt:file', $nt[1]['name']);
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::getNodeTypes
     */
    public function testEmptyGetNodeTypes()
    {
        $t = $this->setUpNodeTypeMock(array(), dirname(__FILE__).'/../../../fixtures/empty.xml');

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
        $dom->load(dirname(__FILE__).'/../../../fixtures/accessibleWorkspaces.xml');

        $t = $this->getTransportMock('testuri');
        $request = $this->getRequestMock($dom, array('setBody', 'setDepth'));
        $t->expects($this->once())
            ->method('getRequest')
            ->with(Request::PROPFIND, 'testuri/')
            ->will($this->returnValue($request));
        $request->expects($this->once())
            ->method('setBody')
            ->with($this->getTransportMock()->buildPropfindRequestMock(array('D:workspace')));
        $request->expects($this->once())
            ->method('setDepth')
            ->with(1);

        $names = $t->getAccessibleWorkspaceNames();
        $this->assertSame(array('default', 'tests', 'security'), $names);
    }

    /**
     * @covers \Jackalope\Transport\Davex\Client::normalizeUri
     */
    public function testNormalizeUri()
    {
        $factory = new \Jackalope\Factory;
        $transport = new ClientMock($factory, '');

        $this->assertEquals('foo/bar', $transport->normalizeUriMock('foo/bar'), 'Relative uri was prepended with workspaceUriRoot');
        $this->assertEquals('testWorkspaceUriRoot/foo/bar', $transport->normalizeUriMock('/foo/bar'), 'Absolute uri was not prepended with workspaceUriRoot');
        $this->assertEquals('foo', $transport->normalizeUriMock('foo'), 'Relative uri was prepended with workspaceUriRoot');
        $this->assertEquals('testWorkspaceUriRoot/foo', $transport->normalizeUriMock('/foo'), 'Absolute uri was not prepended with workspaceUriRoot');
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

    public function buildNodeTypesRequestMock(Array $params)
    {
        return $this->buildNodeTypesRequest($params);
    }

    public function buildReportRequestMock($name = '')
    {
        return $this->buildReportRequest($name);
    }

    public function buildPropfindRequestMock($args = array())
    {
        return $this->buildPropfindRequest($args);
    }

    public function buildLocateRequestMock($arg = '')
    {
        return $this->buildLocateRequest($arg);
    }

    public function setCredentials($credentials)
    {
        $this->credentials = $credentials;
    }

    public function getRequestMock($method, $uri)
    {
        return $this->getRequest($method, $uri);
    }

    public function normalizeUriMock($uri)
    {
        return $this->normalizeUri($uri);
    }
}
