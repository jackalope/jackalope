<?php

namespace Jackalope;

use DOMDocument;
use Jackalope\NodeType\ItemDefinition;
use Jackalope\NodeType\NodeDefinition;
use Jackalope\NodeType\NodeType;
use Jackalope\Transaction\UserTransaction;
use Jackalope\Transport\TransportInterface;
use PHPCR\NodeType\PropertyDefinitionInterface;
use PHPCR\SimpleCredentials;
use Jackalope\NodeType\NodeTypeManager;
use Jackalope\NodeType\NodeTypeXmlConverter;
use PHPUnit\Framework\TestCase as BaseTestCase;
use ReflectionClass;

abstract class TestCase extends BaseTestCase
{
    protected $config;
    protected $credentials;

    protected $JSON = '{":jcr:primaryType":"Name","jcr:primaryType":"rep:root","jcr:system":{},"tests_level1_access_base":{}}';

    protected function setUp(): void
    {
        foreach ($GLOBALS as $cfgKey => $value) {
            if ('phpcr.' === substr($cfgKey, 0, 6)) {
                $this->config[substr($cfgKey, 6)] = $value;
            }
        }

        $user = isset($this->config['user']) ? $this->config['user'] : null;
        $pass = isset($this->config['pass']) ? $this->config['pass'] : null;

        $this->credentials = new SimpleCredentials($user, $pass);
    }

    /**
     * Map return values to methods on given mock objects.
     *
     * @param
     * @param array
     */
    private function mapMockMethodReturnValues($mock, $methodsToValues)
    {
        foreach ($methodsToValues as $method => $value) {
            $mock->expects($this->any())
                ->method($method)
                ->will($this->returnValue($value));
        }

        return $mock;
    }

    /**
     * Get a mock object for the read only transport.
     *
     * @return TransportInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getTransportStub()
    {
        $transport = $this->getMockBuilder(TransportInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $transport->expects($this->any())
            ->method('getNode')
            ->will($this->returnValue(json_decode($this->JSON)))
        ;

        $dom = new DOMDocument();
        $dom->load(__DIR__ . '/../fixtures/nodetypes.xml');
        $transport->expects($this->any())
            ->method('getNodeTypes')
            ->will($this->returnValue($dom));

        $transport->expects($this->any())
            ->method('getNodePathForIdentifier')
            ->will($this->returnValue('/jcr:root/uuid/to/path'));

        $transport->expects($this->any())
            ->method('getNodes')
            ->will($this->returnValue(['/jcr:root/tests_level1_access_base' => [], '/jcr:root/jcr:system' => []]));

        return $transport;
    }

    /**
     * Get a mock object for a session.
     *
     * @return Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getSessionMock()
    {
        $mock = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();

        $mock->expects($this->any())
             ->method('getWorkspace')
             ->will($this->returnValue($this->getWorkspaceMock()))
        ;

        $mock->expects($this->any())
             ->method('getRepository')
             ->will($this->returnValue($this->getRepositoryMock()))
        ;

        return $mock;
    }

    /**
     * Get a mock object for a workspace.
     *
     * @return Workspace|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getWorkspaceMock()
    {
        $factory = new Factory();
        $mock = $this->getMockBuilder(Workspace::class)
            ->setMethods(['getTransactionManager', 'getNodeTypeManager'])
            ->setConstructorArgs([$factory])
            ->setMockClassName('')
            ->disableOriginalConstructor()
            ->getMock();
        $mock->expects($this->any())
             ->method('getTransactionManager')
             ->will($this->returnValue($this->getInactiveTransactionMock()));

        return $mock;
    }

    /**
     * Get a mock object for an inactive transaction.
     *
     * @return UserTransaction|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getInactiveTransactionMock()
    {
        $mock = $this->getMockBuilder(UserTransaction::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $mock->expects($this->any())
             ->method('inTransaction')
             ->will($this->returnValue(false))
        ;

        return $mock;
    }

    /**
     * Get a mock object for a repository.
     *
     * @param array $methodValueMap
     *
     * @return Repository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRepositoryMock($methodValueMap = [])
    {
        $mock = $this->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->mapMockMethodReturnValues($mock, $methodValueMap);

        return $mock;
    }

    /**
     * Get a mock object for the ObjectManager.
     *
     * @param array $methodValueMap
     *
     * @return ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getObjectManagerMock($methodValueMap = [])
    {
        $mock = $this->getMockBuilder('Jackalope\ObjectManager')->disableOriginalConstructor()->getMock();
        $this->mapMockMethodReturnValues($mock, $methodValueMap);

        return $mock;
    }

    /**
     * Get a mock object for a node.
     *
     * NOTE: This and other mock methods are public because they need to be accessed from within callbacks sometimes.
     *
     * @param array $methodValueMap
     *
     * @return Node|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getNodeMock($methodValueMap = [])
    {
        $mock = $this->getMockBuilder(Node::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mapMockMethodReturnValues($mock, $methodValueMap);

        return $mock;
    }

    /**
     * Get a mock object for a node type
     *
     * @param array $methodValueMap
     *
     * @return Node|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getNodeTypeMock($methodValueMap = [])
    {
        $mock = $this->getMockBuilder(NodeType::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->mapMockMethodReturnValues($mock, $methodValueMap);

        return $mock;
    }

    /**
     * Get a mock object for an item definition
     *
     * @param array $methodValueMap
     *
     * @return Node|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getItemDefinitionMock($methodValueMap = [])
    {
        $mock = $this->getMockBuilder(ItemDefinition::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->mapMockMethodReturnValues($mock, $methodValueMap);

        return $mock;
    }

    /**
     * Get a mock object for an item definition
     *
     * @param array $methodValueMap
     *
     * @return Node|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getNodeDefinitionMock($methodValueMap = [])
    {
        $mock = $this->getMockBuilder(NodeDefinition::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->mapMockMethodReturnValues($mock, $methodValueMap);

        return $mock;
    }

    /**
     * Get a mock object for an item definition
     *
     * @param array $methodValueMap
     *
     * @return Node|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getPropertyDefinitionMock($methodValueMap = [])
    {
        $mock = $this->getMockBuilder(PropertyDefinitionInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->mapMockMethodReturnValues($mock, $methodValueMap);

        return $mock;
    }

    public function getPropertyMock($methodValueMap = [])
    {
        $mock = $this->getMockBuilder(Property::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->mapMockMethodReturnValues($mock, $methodValueMap);

        return $mock;
    }

    /**
     * Get a mock object for a node.
     *
     * @param array $methodValueMap
     *
     * @return NodeTypeManager|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getNodeTypeManagerMock($methodValueMap = [])
    {
        $mock = $this->getMockBuilder(NodeTypeManager::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->mapMockMethodReturnValues($mock, $methodValueMap);

        return $mock;
    }

    /**
     * Get the (real) node type manager with a mock object manager that returns
     * real node type data for getNodeTypes.
     *
     * @return NodeTypeManager
     */
    protected function getNodeTypeManager()
    {
        $factory = new Factory();
        $dom = new DOMDocument();
        $dom->load(__DIR__ . '/../fixtures/nodetypes.xml');
        $converter = new NodeTypeXmlConverter($factory);
        $om = $this->getObjectManagerMock();
        $om->expects($this->any())
            ->method('getNodeTypes')
            ->will($this->returnValue($converter->getNodeTypesFromXml($dom)))
        ;
        $ns = $this->getMockBuilder(NamespaceRegistry::class)->disableOriginalConstructor()->getMock();

        $ntm = new NodeTypeManager($factory, $om, $ns);
        // we need to initialize as getting a single node type calls a different method on the om.
        $ntm->getAllNodeTypes();

        return $ntm;
    }

    /**
     * Call a protected or private method on an object instance
     *
     * @param  object $instance The instance to call the method on
     * @param  string $method   The protected or private method to call
     * @param  array  $args     The arguments to the called method
     *
     * @return mixed  The result of the method call
     */
    protected function getAndCallMethod($instance, $method, $args = [])
    {
        $class = new ReflectionClass(get_class($instance));
        $method = $class->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($instance, $args);
    }

    /**
     * Get the value of a protected or private property of an object
     *
     * @param  object $instance
     * @param  string $attributeName
     *
     * @return mixed
     */
    protected function getAttributeValue($instance, $attributeName)
    {
        $class = new ReflectionClass(get_class($instance));
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
        $class = new ReflectionClass(get_class($instance));
        $prop = $class->getProperty($attributeName);
        $prop->setAccessible(true);

        $this->assertEquals($expectedValue, $prop->getValue($instance));
    }

    protected function setAttributeValue($instance, $attributeName, $value)
    {
        $class = new ReflectionClass(get_class($instance));
        $prop = $class->getProperty($attributeName);
        $prop->setAccessible(true);

        $prop->setValue($instance, $value);
    }

    /**
     * Build a DOMElement from an xml string
     *
     * @param  string      $xml The xml extract to build the DOMElement from
     *
     * @return \DOMElement
     */
    protected function getDomElement($xml)
    {
        $doc = new DOMDocument();
        $doc->loadXML('<wrapper>' . $xml . '</wrapper>');
        $list = $doc->getElementsByTagName('wrapper');

        return $list->item(0);
    }
}
