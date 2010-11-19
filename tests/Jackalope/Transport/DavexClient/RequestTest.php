<?php

namespace  Jackalope\Transport\DavexClient;

class RequestTest extends \PHPUnit_Framework_TestCase {

    /**
     * @covers  \Jackalope\Transport\DavexClient\Request::getDomObject
     */
    public function testGetDomObject() {
        $request = new RequestProxy();
        $request->getDomObject();
        $this->assertAttributeInstanceOf('DOMDocument', 'dom', $request);
    }
}

class RequestProxy extends \Jackalope\Transport\DavexClient\Request {

    public function getDomObject() {
        return parent::getDomObject();
    }
}