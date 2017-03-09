<?php

namespace Jackalope\NodeType;

use ArrayObject;
use Jackalope\TestCase;
use Jackalope\Transport\AddNodeOperation;
use PHPCR\NamespaceException;
use PHPCR\PropertyType;
use PHPCR\RepositoryException;
use PHPCR\ValueFormatException;

class NodeProcessorTest extends TestCase
{
    /**
     * @var NodeProcessor
     */
    private $processor;

    public function setUp()
    {
        $this->processor = new NodeProcessor('dtl', new ArrayObject([
            'ns' => 'Namespace',
            'dtl' => 'http://www.dantleech.com/ns',
        ]));
    }

    public function testChildDefMandatoryNotPresent()
    {
        $this->expectException(RepositoryException::class);

        $nodeDefinition = $this->getNodeDefinitionMock([
            'getName' => 'node-definition',
            'isMandatory' => true,
            'isAutoCreated' => false,
        ]);

        $nodeType = $this->getNodeTypeMock([
            'getDeclaredChildNodeDefinitions' => [$nodeDefinition],
            'getDeclaredPropertyDefinitions' => [],
            'getDeclaredSupertypes' => [],
            'getName' => 'node-type-1',
        ]);

        $node = $this->getNodeMock([
            'getPrimaryNodeType' => $nodeType,
            'getMixinNodeTypes' => [],
            'getProperties' => [],
            'getName' => 'node1',
            'getPath' => 'path/to/node',
        ]);

        $this->processor->process($node);
    }

    public function testChildDefAutoCreated()
    {
        $newNode = $this->getNodeMock();
        $nodeDefinition = $this->getNodeDefinitionMock([
            'getName' => 'node-definition',
            'isAutoCreated' => true,
            'getRequiredPrimaryTypeNames' => ['type1', 'type2'],
        ]);

        $nodeType = $this->getNodeTypeMock([
            'getDeclaredChildNodeDefinitions' => [$nodeDefinition],
            'getDeclaredPropertyDefinitions' => [],
            'getDeclaredSupertypes' => [],
            'getName' => 'node-type-1',
        ]);

        $node = $this->getNodeMock([
            'getPrimaryNodeType' => $nodeType,
            'getMixinNodeTypes' => [],
            'getProperties' => [],
            'getName' => 'node1',
            'getPath' => 'path/to/node',
        ]);

        $node->expects($this->once())
            ->method('addNode')
            ->with('node-definition', 'type1')
            ->will($this->returnValue($newNode));

        $res = $this->processor->process($node);

        $this->assertInternalType('array', $res);
        $this->assertCount(1, $res);
        $operation = reset($res);
        $this->assertInstanceOf(AddNodeOperation::class, $operation);
        $this->assertSame($newNode, $operation->node);
    }

    public function testPropertyDefMandatoryNotPresent()
    {
        $this->expectException(RepositoryException::class);

        $propertyDefinition = $this->getPropertyDefinitionMock([
            'getName' => 'property-definition',
            'isMandatory' => true,
            'isAutoCreated' => false,
        ]);

        $nodeType = $this->getNodeTypeMock([
            'getDeclaredChildNodeDefinitions' => [],
            'getDeclaredPropertyDefinitions' => [$propertyDefinition],
            'getDeclaredSupertypes' => [],
            'getName' => 'node-type-1',
        ]);

        $node = $this->getNodeMock([
            'getPrimaryNodeType' => $nodeType,
            'getMixinNodeTypes' => [],
            'getProperties' => [],
            'getName' => 'node1',
            'getPath' => 'path/to/node',
        ]);

        $this->processor->process($node);
    }

    public function testPropertyDefsAutoCreated()
    {
        $jcrUuidProperty = $this->getPropertyDefinitionMock([
            'getName' => 'jcr:uuid',
            'isAutoCreated' => true,
            'getRequiredType' => 'String',
        ]);

        $jcrCreatedByProperty = $this->getPropertyDefinitionMock([
            'getName' => 'jcr:createdBy',
            'isAutoCreated' => true,
        ]);

        $jcrModifiedByProperty = $this->getPropertyDefinitionMock([
            'getName' => 'jcr:lastModifiedBy',
            'isAutoCreated' => true,
        ]);

        $jcrCreatedProperty = $this->getPropertyDefinitionMock([
            'getName' => 'jcr:created',
            'isAutoCreated' => true,
        ]);

        $jcrLastModifiedProperty = $this->getPropertyDefinitionMock([
            'getName' => 'jcr:lastModified',
            'isAutoCreated' => true,
        ]);

        $jcrETagProperty = $this->getPropertyDefinitionMock([
            'getName' => 'jcr:etag',
            'isAutoCreated' => true,
        ]);

        $userPropertySingle = $this->getPropertyDefinitionMock([
            'getName' => 'dtl:single',
            'isAutoCreated' => true,
            'getDefaultValues' => ['one', 'two']
        ]);

        $userPropertyMultiple = $this->getPropertyDefinitionMock([
            'getName' => 'dtl:multiple',
            'isAutoCreated' => true,
            'isMultiple' => true,
            'getDefaultValues' => ['one', 'two']
        ]);

        $nodeType = $this->getNodeTypeMock([
            'getDeclaredChildNodeDefinitions' => [],
            'getDeclaredPropertyDefinitions' => [
                $jcrUuidProperty,
                $jcrCreatedByProperty,
                $jcrModifiedByProperty,
                $jcrCreatedProperty,
                $jcrLastModifiedProperty,
                $jcrETagProperty,
                $userPropertySingle,
                $userPropertyMultiple,
            ],
            'getDeclaredSupertypes' => [],
            'getName' => 'node-type-1',
        ]);

        $node = $this->getNodeMock([
            'getPrimaryNodeType' => $nodeType,
            'getMixinNodeTypes' => [],
            'getProperties' => [],
        ]);

        // expectations
        $node->expects($this->any())
            ->method('setProperty')
            ->withConsecutive(
                ['jcr:uuid', $this->anything(), 'String'],
                ['jcr:createdBy', 'dtl', null],
                ['jcr:lastModifiedBy', 'dtl', null],
                ['jcr:created', $this->anything(), null],
                ['jcr:lastModified', $this->anything(), null],
                ['jcr:etag', 'TODO: generate from binary properties of this node', null],
                ['dtl:single', 'one', null],
                ['dtl:multiple', ['one', 'two'], null]
            );

        $this->processor->process($node);
    }

    public function testPropertyAutoCreatedNoDefaults()
    {
        $this->expectException(RepositoryException::class);
        $this->expectExceptionMessage('No default value for autocreated property');

        $userPropertySingle = $this->getPropertyDefinitionMock([
            'getName' => 'dtl:single',
            'isAutoCreated' => true,
            'getDefaultValues' => []
        ]);

        $nodeType = $this->getNodeTypeMock([
            'getDeclaredChildNodeDefinitions' => [],
            'getDeclaredPropertyDefinitions' => [$userPropertySingle],
            'getDeclaredSupertypes' => [],
            'getName' => 'node-type-1',
        ]);

        $node = $this->getNodeMock([
            'getPrimaryNodeType' => $nodeType,
            'getMixinNodeTypes' => [],
            'getProperties' => [],
            'getName' => 'node1',
            'getPath' => 'path/to/node',
        ]);

        $this->processor->process($node);
    }

    public function testPropertyDefsAutoCreatedUpdate()
    {
        $jcrModifiedByPropertyDefinition = $this->getPropertyDefinitionMock([
            'getName' => 'jcr:lastModifiedBy',
            'isAutoCreated' => true,
        ]);

        $jcrLastModifiedPropertyDefinition = $this->getPropertyDefinitionMock([
            'getName' => 'jcr:lastModified',
            'isAutoCreated' => true,
        ]);
        $jcrETagPropertyDefinition = $this->getPropertyDefinitionMock([
            'getName' => 'jcr:etag',
            'isAutoCreated' => true,
        ]);

        $jcrModifiedByProperty = $this->getPropertyMock();
        $jcrModifiedProperty = $this->getPropertyMock();
        $jcrETagProperty = $this->getPropertyMock();

        $nodeType = $this->getNodeTypeMock([
            'getDeclaredChildNodeDefinitions' => [],
            'getDeclaredPropertyDefinitions' => [
                $jcrModifiedByPropertyDefinition,
                $jcrLastModifiedPropertyDefinition,
                $jcrETagPropertyDefinition,
            ],
            'getDeclaredSupertypes' => [],
            'getName' => 'node-type-1',
        ]);

        $node = $this->getNodeMock([
            'getPrimaryNodeType' => $nodeType,
            'getMixinNodeTypes' => [],
            'getProperties' => [],
        ]);

        $node->expects($this->any())
            ->method('hasProperty')
            ->will($this->returnValue(true));

        // expectations
        $jcrModifiedByProperty->expects($this->once())->method('setValue');
        $jcrModifiedProperty->expects($this->once())->method('setValue');

        // todo: etags
        $jcrETagProperty->expects($this->never())->method('setValue');

        $node->expects($this->any())
            ->method('getProperty')
            ->withConsecutive(
                ['jcr:lastModifiedBy'],
                ['jcr:lastModified'],
                ['jcr:etag']
            )
            ->will($this->onConsecutiveCalls($jcrModifiedByProperty, $jcrModifiedProperty, $jcrETagProperty));

        $this->processor->process($node);
    }

    public function providePropertyValidation()
    {
        return [
            [
                [
                    'getType' => PropertyType::NAME,
                    'isMultiple' => false,
                    'getValue' => 'hello',
                ],
                null
            ],
            [
                [
                    'getType' => PropertyType::NAME,
                    'isMultiple' => false,
                    'getValue' => 'foo:hello',
                    'getPath' => '/path/to',
                ],
                'Invalid value for NAME property type at "/path/to", the namespace prefix "foo" does not exist'
            ],
            [
                [
                    'getType' => PropertyType::PATH,
                    'isMultiple' => false,
                    'getValue' => '/path/to/something',
                    'getPath' => '/path/to',
                ],
                null,
            ],
            [
                [
                    'getType' => PropertyType::PATH,
                    'isMultiple' => false,
                    'getValue' => '  pathto££333+_123£³[]/something[&&"£$]/',
                    'getPath' => '/path/to',
                ],
                'Value "  pathto££333+_123£³[]/something[&&"£$]/" for PATH property at "/path/to" is invalid',
            ],
            [
                [
                    'getType' => PropertyType::URI,
                    'isMultiple' => false,
                    'getValue' => 'http://domain.dom',
                    'getPath' => '/path/to',
                ],
                null,
            ],
            [
                [
                    'getType' => PropertyType::URI,
                    'isMultiple' => false,
                    'getValue' => 'http://domain.dom  sd',
                    'getPath' => '/path/to',
                ],
                'Invalid value "http://domain.dom  sd" for URI property at "/path/to". Value has to comply with RFC 3986',
            ],
            [
                [
                    'getType' => PropertyType::STRING,
                    'isMultiple' => false,
                    'getValue' => 'some string',
                    'getPath' => '/path/to',
                ],
                null
            ],
        ];
    }

    /**
     * @dataProvider providePropertyValidation
     */
    public function testPropertyValidation($propertyConfig, $exception = null)
    {
        $property = $this->getPropertyMock($propertyConfig);

        if ($exception) {
            $this->expectException(ValueFormatException::class);
            $this->expectExceptionMessage($exception);
        }

        $nodeType = $this->getNodeTypeMock([
            'getDeclaredChildNodeDefinitions' => [],
            'getDeclaredPropertyDefinitions' => [],
            'getDeclaredSupertypes' => [],
            'getName' => 'node-type-1',
        ]);

        $node = $this->getNodeMock([
            'getPrimaryNodeType' => $nodeType,
            'getMixinNodeTypes' => [],
            'getProperties' => [$property],
            'getName' => 'node1',
            'getPath' => 'path/to/node',
        ]);

        $this->processor->process($node);
    }

    public function provideNamespaceValidation()
    {
        return [
            ['no-namespace', true],
            ['ns:registered-namespace', true],
            ['dtl:registered-namespace', true],
            ['ltd:unkown-namespace', false],
        ];
    }

    /**
     * @dataProvider provideNamespaceValidation
     */
    public function testNamespaceValidation($nodeName, $isValid)
    {
        if (false === $isValid) {
            $this->expectException(NamespaceException::class);
            $this->expectExceptionMessage('is not known');
        }

        $nodeType = $this->getNodeTypeMock([
            'getDeclaredChildNodeDefinitions' => [],
            'getDeclaredPropertyDefinitions' => [],
            'getDeclaredSupertypes' => [],
            'getName' => 'node-type-1',
        ]);
        $node = $this->getNodeMock([
            'getPrimaryNodeType' => $nodeType,
            'getMixinNodeTypes' => [],
            'getProperties' => [],
            'getName' => $nodeName,
            'getPath' => 'path/to/node',
        ]);

        $this->processor->process($node);
    }

    public function providePropertyValidationOutOfRangeCharacters()
    {
        return [
            ['This is valid too!'.$this->translateCharFromCode('\u0009'), true],
            ['This is valid', true],
            [$this->translateCharFromCode('\uD7FF'), true],
            ['This is on the edge, but valid too.'. $this->translateCharFromCode('\uFFFD'), true],
            [$this->translateCharFromCode('\u10000'), true],
            [$this->translateCharFromCode('\u10FFFF'), true],
            [$this->translateCharFromCode('\u0001'), false],
            [$this->translateCharFromCode('\u0002'), false],
            [$this->translateCharFromCode('\u0003'), false],
            [$this->translateCharFromCode('\u0008'), false],
            [$this->translateCharFromCode('\uFFFF'), false],
        ];
    }

    /**
     * @dataProvider providePropertyValidationOutOfRangeCharacters
     */
    public function testPropertyValidationOutOfRangeCharacters($value, $isValid)
    {
        $property = $this->getPropertyMock([
            'getType' => PropertyType::STRING,
            'isMultiple' => false,
            'getValue' => $value,
            'getPath' => '/path/to',
        ]);

        if (false === $isValid) {
            $this->expectException(ValueFormatException::class);
            $this->expectExceptionMessage('Invalid character detected in value');
        }

        $nodeType = $this->getNodeTypeMock([
            'getDeclaredChildNodeDefinitions' => [],
            'getDeclaredPropertyDefinitions' => [],
            'getDeclaredSupertypes' => [],
            'getName' => 'node-type-1',
        ]);

        $node = $this->getNodeMock([
            'getPrimaryNodeType' => $nodeType,
            'getMixinNodeTypes' => [],
            'getProperties' => [$property],
            'getName' => 'node1',
            'getPath' => 'path/to/node',
        ]);

        $this->processor->process($node);
    }

    private function translateCharFromCode($char)
    {
        return json_decode('"'.$char.'"');
    }
}
