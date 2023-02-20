<?php

namespace Jackalope;

use Jackalope\NodeType\ItemDefinition;
use Jackalope\NodeType\NodeDefinition;
use Jackalope\NodeType\NodeType;
use Jackalope\NodeType\NodeTypeManager;
use Jackalope\NodeType\NodeTypeXmlConverter;
use Jackalope\Transport\TransportInterface;
use PHPCR\NamespaceRegistryInterface;
use PHPCR\NodeType\PropertyDefinitionInterface;
use PHPCR\PropertyInterface;
use PHPCR\RepositoryInterface;
use PHPCR\SimpleCredentials;
use PHPCR\Transaction\UserTransactionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected $config;
    protected $credentials;

    private string $JSON = '{":jcr:primaryType":"Name","jcr:primaryType":"rep:root","jcr:system":{},"tests_level1_access_base":{}}';

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
    protected function getTransportStub(): TransportInterface
    {
        $transport = $this->getMockBuilder(TransportInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $transport
            ->method('getNode')
            ->willReturn(json_decode($this->JSON))
        ;

        $dom = new \DOMDocument();
        $dom->load(__DIR__.'/../fixtures/nodetypes.xml');
        $transport
            ->method('getNodeTypes')
            ->willReturn($this->dom2Array($dom))
        ;

        $transport
            ->method('getNodePathForIdentifier')
            ->willReturn('/jcr:root/uuid/to/path')
        ;

        $transport
            ->method('getNodes')
            ->willReturn(['/jcr:root/tests_level1_access_base' => [], '/jcr:root/jcr:system' => []])
        ;

        return $transport;
    }

    protected function dom2Array(\DOMNode $document): array
    {
        $array = [];

        if ($document->hasAttributes()) {
            foreach ($document->attributes as $attribute) {
                $array['_attributes'][$attribute->name] = $attribute->value;
            }
        }

        // handle classic node
        if (XML_ELEMENT_NODE === $document->nodeType) {
            $array['_type'] = $document->nodeName;
            if ($document->hasChildNodes()) {
                $children = $document->childNodes;
                for ($i = 0; $i < $children->length; ++$i) {
                    $child = $this->dom2Array($children->item($i));

                    // don't keep textnode with only spaces and newline
                    if (!empty($child)) {
                        $array['_children'][] = $child;
                    }
                }
            }

        // handle text node
        } elseif (XML_TEXT_NODE === $document->nodeType || XML_CDATA_SECTION_NODE === $document->nodeType) {
            $value = $document->nodeValue;
            if (!empty($value)) {
                $array['_type'] = '_text';
                $array['_content'] = $value;
            }
        }

        return $array;
    }

    /**
     * @return Session&MockObject
     */
    protected function getSessionMock(): Session
    {
        $mock = $this->createMock(Session::class);

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
     * @return Workspace&MockObject
     */
    protected function getWorkspaceMock(): Workspace
    {
        $factory = new Factory();
        $mock = $this->getMockBuilder(Workspace::class)
            ->setMethods(['getTransactionManager', 'getNodeTypeManager'])
            ->setConstructorArgs([$factory])
            ->setMockClassName('')
            ->disableOriginalConstructor()
            ->getMock();
        $mock
             ->method('getTransactionManager')
             ->willReturn($this->getInactiveTransactionMock())
        ;

        return $mock;
    }

    /**
     * @return UserTransactionInterface&MockObject
     */
    protected function getInactiveTransactionMock(): UserTransactionInterface
    {
        $mock = $this->createMock(UserTransactionInterface::class);

        $mock
             ->method('inTransaction')
             ->willReturn(false)
        ;

        return $mock;
    }

    /**
     * @return RepositoryInterface&MockObject
     */
    protected function getRepositoryMock(array $methodValueMap = []): RepositoryInterface
    {
        $mock = $this->createMock(RepositoryInterface::class);

        $this->mapMockMethodReturnValues($mock, $methodValueMap);
        $mock->method('getDescriptor')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case Repository::OPTION_TRANSACTIONS_SUPPORTED:
                    case Repository::JACKALOPE_OPTION_STREAM_WRAPPER:
                        return true;
                    case RepositoryInterface::OPTION_LOCKING_SUPPORTED:
                        return false;
                }

                throw new \Exception('todo: '.$key);
            })
        ;

        return $mock;
    }

    /**
     * @return ObjectManager&MockObject
     */
    protected function getObjectManagerMock(array $methodValueMap = []): ObjectManager
    {
        $mock = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $this->mapMockMethodReturnValues($mock, $methodValueMap);

        return $mock;
    }

    /**
     * NOTE: This and other mock methods are public because they need to be accessed from within callbacks sometimes.
     *
     * @return Node&MockObject
     */
    public function getNodeMock(array $methodValueMap = []): Node
    {
        $mock = $this->getMockBuilder(Node::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mapMockMethodReturnValues($mock, $methodValueMap);

        return $mock;
    }

    /**
     * @return NodeType&MockObject
     */
    public function getNodeTypeMock(array $methodValueMap = []): NodeType
    {
        $mock = $this->getMockBuilder(NodeType::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->mapMockMethodReturnValues($mock, $methodValueMap);

        return $mock;
    }

    /**
     * @return ItemDefinition&MockObject
     */
    public function getItemDefinitionMock(array $methodValueMap = []): ItemDefinition
    {
        $mock = $this->getMockBuilder(ItemDefinition::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->mapMockMethodReturnValues($mock, $methodValueMap);

        return $mock;
    }

    /**
     * @return NodeDefinition&MockObject
     */
    public function getNodeDefinitionMock(array $methodValueMap = []): NodeDefinition
    {
        $mock = $this->getMockBuilder(NodeDefinition::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->mapMockMethodReturnValues($mock, $methodValueMap);

        return $mock;
    }

    /**
     * @return PropertyDefinitionInterface&MockObject
     */
    public function getPropertyDefinitionMock(array $methodValueMap = []): PropertyDefinitionInterface
    {
        $mock = $this->createMock(PropertyDefinitionInterface::class);

        $this->mapMockMethodReturnValues($mock, $methodValueMap);

        return $mock;
    }

    /**
     * @return PropertyInterface&MockObject
     */
    public function getPropertyMock(array $methodValueMap = []): PropertyInterface
    {
        $mock = $this->createMock(PropertyInterface::class);

        $this->mapMockMethodReturnValues($mock, $methodValueMap);

        return $mock;
    }

    /**
     * @return NodeTypeManager&MockObject
     */
    public function getNodeTypeManagerMock(array $methodValueMap = []): NodeTypeManager
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
        $dom = new \DOMDocument();
        $dom->load(__DIR__.'/../fixtures/nodetypes.xml');
        $converter = new NodeTypeXmlConverter($factory);
        $om = $this->createMock(ObjectManager::class);
        $om
            ->method('getNodeTypes')
            ->willReturn($converter->getNodeTypesFromXml($dom))
        ;
        $ns = $this->createMock(NamespaceRegistryInterface::class);

        $ntm = new NodeTypeManager($factory, $om, $ns);
        // we need to initialize as getting a single node type calls a different method on the om.
        $ntm->getAllNodeTypes();

        return $ntm;
    }

    /**
     * Call a protected or private method on an object instance.
     *
     * @param object $instance   The instance to call the method on
     * @param string $methodName The protected or private method to call
     * @param array  $args       The arguments to the called method
     *
     * @return mixed The result of the method call
     */
    protected function getAndCallMethod($instance, string $methodName, array $args = [])
    {
        $class = new \ReflectionClass(get_class($instance));
        $method = $class->getMethod($methodName);
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
    protected function myAssertAttributeEquals($expectedValue, string $attributeName, $instance): void
    {
        $class = new \ReflectionClass(get_class($instance));
        $prop = $class->getProperty($attributeName);
        $prop->setAccessible(true);

        $this->assertEquals($expectedValue, $prop->getValue($instance));
    }

    protected function setAttributeValue($instance, string $attributeName, $value): void
    {
        $class = new \ReflectionClass(get_class($instance));
        $prop = $class->getProperty($attributeName);
        $prop->setAccessible(true);

        $prop->setValue($instance, $value);
    }

    protected function getDomElement(string $xml): \DOMNode
    {
        $doc = new \DOMDocument();
        $doc->loadXML('<wrapper>'.$xml.'</wrapper>');
        $list = $doc->getElementsByTagName('wrapper');

        return $list->item(0);
    }
}
