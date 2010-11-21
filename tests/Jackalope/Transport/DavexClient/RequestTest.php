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

        $proxyGenerator = new \Tests\Unittests\ProxyObject;
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
     * @covers  \Jackalope\Transport\DavexClient\Request::getTypeObject
     */
    public function testGetTypeObject() {
        $request = $this->getRequestProxy('getTypeObject', array('NodeTypes', array()));
        $this->assertInstanceOf('\Jackalope\Transport\DavexClient\Requests\NodeTypes', $request->getTypeObject());
    }
}
