<?php

namespace Jackalope;

use PHPCR\SimpleCredentials;

use Jackalope\NodeType\NodeTypeManager;
use Jackalope\NodeType\NodeTypeXmlConverter;

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
        $this->credentials = new SimpleCredentials($this->config['user'], $this->config['pass']);
    }

    protected function getTransportStub()
    {
        $factory = new Factory;
        $transport = $this->getMockBuilder('Jackalope\Transport\TransportInterface')
            ->disableOriginalConstructor()
            ->getMock(array('getNode', 'getNodeTypes', 'getNodePathForIdentifier'), array($factory, 'http://example.com'));

        $transport->expects($this->any())
            ->method('getNode')
            ->will($this->returnValue(json_decode($this->JSON)));

        $dom = new \DOMDocument();
        $dom->load(__DIR__ . '/../fixtures/nodetypes.xml');
        $transport->expects($this->any())
            ->method('getNodeTypes')
            ->will($this->returnValue($dom));

        $transport->expects($this->any())
            ->method('getNodePathForIdentifier')
            ->will($this->returnValue('/jcr:root/uuid/to/path'));

        $transport->expects($this->any())
            ->method('getNodes')
            ->will($this->returnValue(array("/jcr:root/tests_level1_access_base" => array(), "/jcr:root/jcr:system" => array())));

        return $transport;
    }

    protected function getSessionMock($additionalMethodsToMock = array())
    {
        $methodsToMock = array_merge(array('getWorkspace', 'getRepository'), $additionalMethodsToMock);

        $factory = new Factory;
        $mock = $this->getMock('\Jackalope\Session', $methodsToMock, array($factory), '', false);
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
        $factory = new Factory;
        $mock = $this->getMock('\Jackalope\Workspace', array('getTransactionManager'), array($factory), '', false);
        $mock->expects($this->any())
             ->method('getTransactionManager')
             ->will($this->returnValue($this->getInactiveTransactionMock()));

        return $mock;
    }

    protected function getInactiveTransactionMock()
    {
        $factory = new Factory;
        $mock = $this->getMock('Jackalope\Transaction\UserTransaction', array('inTransaction'), array($factory), '', false);
        $mock->expects($this->any())
             ->method('inTransaction')
             ->will($this->returnValue(false));

        return $mock;
    }

    protected function getRepositoryMock()
    {
        $factory = new Factory;
        $mock = $this->getMock('\Jackalope\Repository', array(), array($factory, null, array('transactions'=>false)), '', false);

        return $mock;
    }

    protected function getObjectManagerMock()
    {
        $factory = new Factory;

        return $this->getMock('\Jackalope\ObjectManager', array('getNodeTypes'), array($factory, $this->getTransportStub('/jcr:root'), $this->getSessionMock()));
    }

    /**
     * Get a mock object for a node. No methods are mocked but additional methods to
     * mock can be specified with $methodsToMock.
     * @param  array  $methodsToMock Array of method names to mock
     * @return object
     */
    protected function getNodeMock($methodsToMock = array())
    {
        $node = $this->getMock('\Jackalope\Node', $methodsToMock, array(new Factory(), array(), '', $this->getSessionMock(), $this->getObjectManagerMock()));

        return $node;
    }

    protected function getNodeTypeManager()
    {
        $factory = new Factory;
        $dom = new \DOMDocument();
        $dom->load(__DIR__ . '/../fixtures/nodetypes.xml');
        $converter = new NodeTypeXmlConverter($factory);
        $om = $this->getObjectManagerMock();
        $om->expects($this->any())
            ->method('getNodeTypes')
            ->will($this->returnValue($converter->getNodeTypesFromXml($dom)))
        ;
        $ns = $this->getMockBuilder('Jackalope\NamespaceRegistry')->disableOriginalConstructor()->getMock();

        return new NodeTypeManager($factory, $om, $ns);
    }

    /**
     * Call a protected or private method on an object instance
     * @param  object $instance The instance to call the method on
     * @param  string $method   The protected or private method to call
     * @param  array  $args     The arguments to the called method
     * @return mixed  The result of the method call
     */
    protected function getAndCallMethod($instance, $method, $args = array())
    {
        $class = new \ReflectionClass(get_class($instance));
        $method = $class->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($instance, $args);
    }

    /**
     * Get the value of a protected or private property of an object
     * @param  object $instance
     * @param  string $attributeName
     * @return mixed
     */
    protected function getAttributeValue($instance, $attributeName)
    {
        $class = new \ReflectionClass(get_class($instance));
        $prop = $class->getProperty($attributeName);
        $prop->setAccessible(true);

        return $prop->getValue($instance);
    }

    /**
     * This method is meant to replace the buggy assertAttributeEquals of PHPUnit which
     * does not seem to work properly on classes that extend ArrayIterator.
     *
     * @see https://github.com/sebastianbergmann/phpunit/issues/523
     *
     * @param mixed  $expectedValue The expected value
     * @param string $attributeName The name of the attribute to test
     * @param object $instance      The instance on which to run the test
     */
    protected function myAssertAttributeEquals($expectedValue, $attributeName, $instance)
    {
        $class = new \ReflectionClass(get_class($instance));
        $prop = $class->getProperty($attributeName);
        $prop->setAccessible(true);

        $this->assertEquals($expectedValue, $prop->getValue($instance));
    }

    protected function setAttributeValue($instance, $attributeName, $value)
    {
        $class = new \ReflectionClass(get_class($instance));
        $prop = $class->getProperty($attributeName);
        $prop->setAccessible(true);

        $prop->setValue($instance, $value);
    }

    /**
     * Build a DOMElement from an xml string
     * @param  string      $xml The xml extract to build the DOMElement from
     * @return \DOMElement
     */
    protected function getDomElement($xml)
    {
        $doc = new \DOMDocument();
        $doc->loadXML('<wrapper>' . $xml . '</wrapper>');
        $list = $doc->getElementsByTagName('wrapper');

        return $list->item(0);
    }
}
