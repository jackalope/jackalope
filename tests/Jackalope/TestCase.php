<?php

namespace Jackalope;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    protected $config;
    protected $credentials;

    protected $JSON = '{":jcr:primaryType":"Name","jcr:primaryType":"rep:root","jcr:system":{},"tests_level1_access_base":{}}';

    protected function setUp()
    {
        foreach ($GLOBALS as $cfgKey => $value) {
            if ('phpcr.' === substr($cfgKey, 0, 6)) {
                $this->config[substr($cfgKey, 6)] = $value;
            }
        }
        $this->credentials = new \PHPCR\SimpleCredentials($this->config['user'], $this->config['pass']);
    }

    protected function getTransportStub($path)
    {
        $factory = new \Jackalope\Factory;
        $transport = $this->getMock('\Jackalope\Transport\Jackrabbit\Client', array('getNode', 'getNodeTypes', 'getNodePathForIdentifier'), array($factory, 'http://example.com'));

        $transport->expects($this->any())
            ->method('getNode')
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
        $factory = new \Jackalope\Factory;
        $mock = $this->getMock('\Jackalope\Session', array('getWorkspace', 'getRepository'), array($factory), '', false);
        $mock->expects($this->any())
             ->method('getWorkspace')
             ->will($this->returnValue($this->getWorkspaceMock()));
        $mock->expects($this->any())
             ->method('getRepository')
             ->will($this->returnValue($this->getRepositoryMock()));
        return $mock;
    }

    protected function getWorkspaceMock()
    {
        $factory = new \Jackalope\Factory;
        $mock = $this->getMock('\Jackalope\Workspace', array('getTransactionManager'), array($factory), '', false);
        $mock->expects($this->any())
             ->method('getTransactionManager')
             ->will($this->returnValue($this->getInactiveTransactionMock()));
        return $mock;
    }

    protected function getInactiveTransactionMock()
    {
        $factory = new \Jackalope\Factory;
        $mock = $this->getMock('Jackalope\Transaction\UserTransaction', array('inTransaction'), array($factory), '', false);
        $mock->expects($this->any())
             ->method('inTransaction')
             ->will($this->returnValue(false));
        return $mock;
    }

    protected function getRepositoryMock()
    {
        $factory = new \Jackalope\Factory;
        $mock = $this->getMock('\Jackalope\Repository', array(), array($factory, null, array('transactions'=>false)), '', false);
        return $mock;
    }

    protected function getObjectManagerMock()
    {
        $factory = new \Jackalope\Factory;
        return $this->getMock('\Jackalope\ObjectManager', array('getNodeTypes'), array($factory, $this->getTransportStub('/jcr:root'), $this->getSessionMock()));
    }

    protected function getNodeTypeManager()
    {
        $factory = new \Jackalope\Factory;
        $dom = new \DOMDocument();
        $dom->load(dirname(__FILE__) . '/../fixtures/nodetypes.xml');
        $converter = new \Jackalope\NodeType\NodeTypeXmlConverter($factory);
        $om = $this->getObjectManagerMock();
        $om->expects($this->any())
            ->method('getNodeTypes')
            ->will($this->returnValue($converter->getNodeTypesFromXml($dom)));
        return new \Jackalope\NodeType\NodeTypeManager($factory, $om);
    }
}
