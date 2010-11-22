<?php

namespace Jackalope;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    protected $config;
    protected $configKeys = array('jcr.url', 'jcr.user', 'jcr.pass', 'jcr.workspace', 'jcr.transport');
    protected $credentials;

    protected $JSON = '{":jcr:primaryType":"Name","jcr:primaryType":"rep:root","jcr:system":{},"tests_level1_access_base":{}}';

    protected function setUp()
    {
        foreach ($this->configKeys as $cfgKey) {
            $this->config[substr($cfgKey, 4)] = $GLOBALS[$cfgKey];
        }
        $this->credentials = new \PHPCR\SimpleCredentials($this->config['user'], $this->config['pass']);
    }

    protected function getTransportStub($path)
    {
        $transport = $this->getMock('Jackalope\Transport\DavexClient', array('getItem', 'getNodeTypes', 'getNodePathForIdentifier'), array('http://example.com'));

        $transport->expects($this->any())
            ->method('getItem')
            ->will($this->returnValue(json_decode($this->JSON)));

        $dom = new \DOMDocument();
        $dom->load(dirname(__FILE__) . '/../fixtures/nodetypes.xml');
        $transport->expects($this->any())
            ->method('getNodeTypes')
            ->will($this->returnValue($dom));

        $transport->expects($this->any())
            ->method('getNodePathForIdentifier')
            ->will($this->returnValue('/jcr:root/uuid/to/path'));

        return $transport;
    }

    protected function getSessionMock()
    {
        return $this->getMock('Jackalope\Session', array(), array(), '', false);
    }

    protected function getNodeTypeManager()
    {
        $dom = new \DOMDocument();
        $dom->load(dirname(__FILE__) . '/../fixtures/nodetypes.xml');
        $om = $this->getMock('Jackalope\ObjectManager', array('getNodeTypes'), array($this->getTransportStub('/jcr:root'), $this->getSessionMock()));
        $om->expects($this->any())
            ->method('getNodeTypes')
            ->will($this->returnValue($dom));
        return new \Jackalope\NodeType\NodeTypeManager($om);
    }
}
