<?php

namespace Jackalope;

use DOMDocument;
use Jackalope\NodeType\ItemDefinition;
use Jackalope\NodeType\NodeDefinition;
use Jackalope\NodeType\NodeType;
use Jackalope\NodeType\NodeTypeManager;
use Jackalope\NodeType\NodeTypeXmlConverter;
use Jackalope\Transaction\UserTransaction;
use Jackalope\Transport\TransportInterface;
use PHPCR\NodeType\PropertyDefinitionInterface;
use PHPCR\SimpleCredentials;
use PHPUnit\Framework\MockObject\MockObject;
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
            if (0 === strpos($cfgKey, 'phpcr.')) {
                $this->config[substr($cfgKey, 6)] = $value;
            }
        }

        $user = $this->config['user'] ?? null;
        $pass = $this->config['pass'] ?? null;

        $this->credentials = new SimpleCredentials($user, $pass);
    }

    /**
     * Map return values to methods on given mock objects.
     */
    private function mapMockMethodReturnValues(MockObject $mock, array $methodsToValues): void
    {
        foreach ($methodsToValues as $method => $value) {
            $mock
                ->method($method)
                ->willReturn($value)
            ;
        }
    }

    /**
     * Get a mock object for the read only transport.
     *
     * @return TransportInterface|MockObject
     */
    protected function getTransportStub()
    {
        $transport = $this->getMockBuilder(TransportInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $transport->expects($this->any())
            ->method('getNode')
            ->willReturn(json_decode($this->JSON))
        ;

        $dom = new DOMDocument();
        $dom->load(__DIR__.'/../fixtures/nodetypes.xml');
        $transport->expects($this->any())
            ->method('getNodeTypes')
            ->willReturn($dom)
        ;

        $transport->expects($this->any())
            ->method('getNodePathForIdentifier')
            ->willReturn('/jcr:root/uuid/to/path')
        ;

        $transport->expects($this->any())
            ->method('getNodes')
            ->willReturn(['/jcr:root/tests_level1_access_base' => [], '/jcr:root/jcr:system' => []])
        ;

        return $transport;
    }

    /**
     * @return Session|MockObject
     */
    protected function getSessionMock()
    {
        $mock = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();

        $mock
             ->method('getWorkspace')
             ->willReturn($this->getWorkspaceMock())
        ;

        $mock
             ->method('getRepository')
             ->willReturn($this->getRepositoryMock())
        ;

        return $mock;
    }

    /**
     * @return Workspace|MockObject
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
             ->willReturn($this->getInactiveTransactionMock())
        ;

        return $mock;
    }

    /**
     * @return UserTransaction|MockObject
     */
    protected function getInactiveTransactionMock()
    {
        $mock = $this->getMockBuilder(UserTransaction::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $mock->expects($this->any())
             ->method('inTransaction')
             ->willReturn(false)
        ;

        return $mock;
    }

    /**
     * @return Repository|MockObject
     */
    protected function getRepositoryMock(array $methodValueMap = [])
    {
        $mock = $this->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->mapMockMethodReturnValues($mock, $methodValueMap);

        return $mock;
    }

    /**
     * @return ObjectManager|MockObject
     */
    protected function getObjectManagerMock(array $methodValueMap = [])
    {
        $mock = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $this->mapMockMethodReturnValues($mock, $methodValueMap);

        return $mock;
    }

    /**
     * NOTE: This and other mock methods are public because they need to be accessed from within callbacks sometimes.
     *
     * @return Node|MockObject
     */
    public function getNodeMock(array $methodValueMap = [])
    {
        $mock = $this->getMockBuilder(Node::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mapMockMethodReturnValues($mock, $methodValueMap);

        return $mock;
    }

    /**
     * @return Node|MockObject
     */
    public function getNodeTypeMock(array $methodValueMap = [])
    {
        $mock = $this->getMockBuilder(NodeType::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->mapMockMethodReturnValues($mock, $methodValueMap);

        return $mock;
    }

    /**
     * @return Node|MockObject
     */
    public function getItemDefinitionMock(array $methodValueMap = [])
    {
        $mock = $this->getMockBuilder(ItemDefinition::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->mapMockMethodReturnValues($mock, $methodValueMap);

        return $mock;
    }

    /**
     * @return Node|MockObject
     */
    public function getNodeDefinitionMock(array $methodValueMap = [])
    {
        $mock = $this->getMockBuilder(NodeDefinition::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->mapMockMethodReturnValues($mock, $methodValueMap);

        return $mock;
    }

    /**
     * @return Node|MockObject
     */
    public function getPropertyDefinitionMock(array $methodValueMap = [])
    {
        $mock = $this->getMockBuilder(PropertyDefinitionInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->mapMockMethodReturnValues($mock, $methodValueMap);

        return $mock;
    }

    /**
     * @return Property|MockObject
     */
    public function getPropertyMock(array $methodValueMap = [])
    {
        $mock = $this->getMockBuilder(Property::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->mapMockMethodReturnValues($mock, $methodValueMap);

        return $mock;
    }

    /**
     * @return NodeTypeManager|MockObject
     */
    public function getNodeTypeManagerMock(array $methodValueMap = [])
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
     */
    protected function getNodeTypeManager(): NodeTypeManager
    {
        $factory = new Factory();
        $dom = new DOMDocument();
        $dom->load(__DIR__.'/../fixtures/nodetypes.xml');
        $converter = new NodeTypeXmlConverter($factory);
        $om = $this->getObjectManagerMock();
        $om
            ->method('getNodeTypes')
            ->willReturn($converter->getNodeTypesFromXml($dom))
        ;
        $ns = $this->getMockBuilder(NamespaceRegistry::class)->disableOriginalConstructor()->getMock();

        $ntm = new NodeTypeManager($factory, $om, $ns);
        // we need to initialize as getting a single node type calls a different method on the om.
        $ntm->getAllNodeTypes();

        return $ntm;
    }

    /**
     * Call a protected or private method on an object instance.
     *
     * @param object $instance The instance to call the method on
     * @param string $method   The protected or private method to call
     * @param array  $args     The arguments to the called method
     *
     * @return mixed The result of the method call
     */
    protected function getAndCallMethod($instance, string $method, array $args = [])
    {
        $class = new ReflectionClass(get_class($instance));
        $method = $class->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($instance, $args);
    }

    /**
     * Get the value of a protected or private property of an object.
     *
     * @param object $instance
     *
     * @return mixed
     */
    protected function getAttributeValue($instance, string $attributeName)
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
    protected function myAssertAttributeEquals($expectedValue, string $attributeName, $instance): void
    {
        $class = new ReflectionClass(get_class($instance));
        $prop = $class->getProperty($attributeName);
        $prop->setAccessible(true);

        $this->assertEquals($expectedValue, $prop->getValue($instance));
    }

    protected function setAttributeValue($instance, string $attributeName, $value): void
    {
        $class = new ReflectionClass(get_class($instance));
        $prop = $class->getProperty($attributeName);
        $prop->setAccessible(true);

        $prop->setValue($instance, $value);
    }

    protected function getDomElement(string $xml): \DOMNode
    {
        $doc = new DOMDocument();
        $doc->loadXML('<wrapper>'.$xml.'</wrapper>');
        $list = $doc->getElementsByTagName('wrapper');

        return $list->item(0);
    }
}
