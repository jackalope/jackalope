<?php

namespace Jackalope\Transport\DavexClient;

class RequestTest extends \PHPUnit_Framework_TestCase {

    /**
     * Provides a proxy of the \Jackalope\Transport\DavexClient\Request class.
     *
     * @param array $methods
     * @param mixed $type
     *
     * @return \Jackalope\Transport\DavexClient\Request Proxy instance exposing invisible methods.
     */
    public function getRequestProxy($methods, $type) {
        if (!is_array($methods)) {
            $methods = array($methods);
        }

        $proxyGenerator = new \Tests\Unittests\ProxyObject;
        return $proxyGenerator->getProxy(
            '\Jackalope\Transport\DavexClient\Request',
            $methods,
            array($type),
            '',
            true
        );
    }

    /**
     * @covers  \Jackalope\Transport\DavexClient\Request::getDomObject
     */
    public function testGetDomObject() {
        $request = $this->getRequestProxy('getDomObject', '');
        $request->getDomObject();
        $this->assertAttributeInstanceOf('DOMDocument', 'dom', $request);
    }
}
