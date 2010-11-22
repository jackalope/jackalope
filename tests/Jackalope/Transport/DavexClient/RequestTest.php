<?php

namespace Jackalope\Transport\DavexClient;

class RequestTest extends \PHPUnit_Framework_TestCase {

    /*************************************************************************/
    /* Fixtures
    /*************************************************************************/

    /**
     * Provides a proxy of the \Jackalope\Transport\DavexClient\Request class.
     *
     * @param array $methods
     * @param array $arguments
     *
     * @return \Jackalope\Transport\DavexClient\Request Proxy instance exposing invisible methods.
     */
    public function getRequestProxy($methods, array $arguments) {
        if (!is_array($methods)) {
            $methods = array($methods);
        }

        $proxyGenerator = new \Tests\Framework\ProxyObject;
        return $proxyGenerator->getProxy(
            '\Jackalope\Transport\DavexClient\Request',
            $methods,
            $arguments,
            '',
            true
        );
    }

    /*************************************************************************/
    /* Tests
    /*************************************************************************/

    /**
     * @covers  \Jackalope\Transport\DavexClient\Request::__construct
     */
    public function testConstruct() {
        $request = new \Jackalope\Transport\DavexClient\Request('OS', array('os' => 'Beastie'));
        $this->assertAttributeEquals('OS', 'type', $request);
        $this->assertAttributeSame(array('os' => 'Beastie'), 'arguments', $request);
    }

    /**
     * @covers  \Jackalope\Transport\DavexClient\Request::getDomObject
     */
    public function testGetDomObject() {
        $request = $this->getRequestProxy('getDomObject', array('', array()));
        $request->getDomObject();
        $this->assertAttributeInstanceOf('DOMDocument', 'dom', $request);
    }

    /**
     * @dataProvider getTypeObjectDataprovider
     * @covers  \Jackalope\Transport\DavexClient\Request::getTypeObject
     */
    public function testGetTypeObject($expected, $requestType) {
        $request = $this->getRequestProxy('getTypeObject', array($requestType, array()));
        $this->assertInstanceOf($expected, $request->getTypeObject());
    }

    /**
     * @covers  \Jackalope\Transport\DavexClient\Request::getTypeObject
     * @expectedException \InvalidArgumentException
     */
    public function testGetTypeObjectExpectingInvalidArgumentException() {
        $request = $this->getRequestProxy('getTypeObject', array('', array()));
        $request->getTypeObject();
    }

    /**
     * @covers  \Jackalope\Transport\DavexClient\Request::setTypeObject
     */
    public function testSetTypeObject() {
        $request = new \Jackalope\Transport\DavexClient\Request('', array());
        $request->setTypeObject(new \Jackalope\Transport\DavexClient\DummyTypeObject);
        $this->assertAttributeInstanceOf('\Jackalope\Interfaces\DavexClient\Request', 'typeObject', $request);
    }

    /**
     * @covers  \Jackalope\Transport\DavexClient\Request::build
     */
    public function testBuild() {
        $request = new \Jackalope\Transport\DavexClient\Request('Dummy', array());
        $request->setTypeObject(new \Jackalope\Transport\DavexClient\DummyTypeObject);
        $this->assertNull($request->build());
    }

    /**
     * @covers  \Jackalope\Transport\DavexClient\Request::__toString
     */
    public function testToString() {
        $request = new \Jackalope\Transport\DavexClient\Request('Dummy', array());
        $request->setTypeObject(new \Jackalope\Transport\DavexClient\DummyTypeObject);
        $this->assertEquals('Dummy object', strval($request));
    }

    /*************************************************************************/
    /* Dataprovider
    /*************************************************************************/

    public static function getTypeObjectDataprovider() {
        return array(
            'get NodeTypes request' => array('\Jackalope\Transport\DavexClient\Requests\NodeTypes', 'NodeTypes'),
            'get Propfind request' => array('\Jackalope\Transport\DavexClient\Requests\Propfind', 'Propfind'),
        );
    }
}

/**
 * Dummy implementation of \Jackalope\Interfaces\DavexClient\Request interface to be able to test.
 *
 */
class DummyTypeObject implements \Jackalope\Interfaces\DavexClient\Request {

    public function build(){}

    public function getXml(){}

    public function __toString(){
        return 'Dummy object';
    }
}
