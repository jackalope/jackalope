<?php

namespace Jackalope\NodeType;

use Jackalope\NodeType\NodeProcessor;
use Jackalope\TestCase;
use PHPCR\PropertyType;

class NodeProcessorTest extends TestCase
{
    /**
     * @var NodeProcessor
     */
    private $processor;

    public function setUp()
    {
        $this->processor = new NodeProcessor('dtl', array(
            'ns' => 'Namespace',
            'dtl' => 'http://www.dantleech.com/ns',
        ));
    }

    /**
     * @expectedException \PHPCR\RepositoryException
     */
    public function testChildDefMandatoryNotPresent()
    {
        $nodeDefinition = $this->getNodeDefinitionMock(array(
            'getName' => 'node-definition',
            'isMandatory' => true,
            'isAutoCreated' => false,
        ));

        $nodeType = $this->getNodeTypeMock(array(
            'getDeclaredChildNodeDefinitions' => array($nodeDefinition),
            'getDeclaredPropertyDefinitions' => array(),
            'getDeclaredSupertypes' => array(),
            'getName' => 'node-type-1',
        ));

        $node = $this->getNodeMock(array(
            'getPrimaryNodeType' => $nodeType,
            'getMixinNodeTypes' => array(),
            'getProperties' => array(),
            'getName' => 'node1',
            'getPath' => 'path/to/node',
        ));

        $this->processor->process($node);
    }

    public function testChildDefAutoCreated()
    {
        $newNode = $this->getNodeMock();
        $nodeDefinition = $this->getNodeDefinitionMock(array(
            'getName' => 'node-definition',
            'isAutoCreated' => true,
            'getRequiredPrimaryTypeNames' => array('type1', 'type2'),
        ));

        $nodeType = $this->getNodeTypeMock(array(
            'getDeclaredChildNodeDefinitions' => array($nodeDefinition),
            'getDeclaredPropertyDefinitions' => array(),
            'getDeclaredSupertypes' => array(),
            'getName' => 'node-type-1',
        ));

        $node = $this->getNodeMock(array(
            'getPrimaryNodeType' => $nodeType,
            'getMixinNodeTypes' => array(),
            'getProperties' => array(),
            'getName' => 'node1',
            'getPath' => 'path/to/node',
        ));
        $node->expects($this->once())
            ->method('addNode')
            ->with('node-definition', 'type1')
            ->will($this->returnValue($newNode));

        $this->processor->process($node);
        $res = $this->processor->getAdditionalOperations();

        $this->assertNotNull($res);
        $this->assertCount(1, $res);
        $operation = reset($res);
        $this->assertInstanceOf('Jackalope\Transport\AddNodeOperation', $operation);
        $this->assertSame($newNode, $operation->node);
    }

    /**
     * @expectedException \PHPCR\RepositoryException
     */
    public function testPropertyDefMandatoryNotPresent()
    {
        $propertyDefinition = $this->getPropertyDefinitionMock(array(
            'getName' => 'property-definition',
            'isMandatory' => true,
            'isAutoCreated' => false,
        ));

        $nodeType = $this->getNodeTypeMock(array(
            'getDeclaredChildNodeDefinitions' => array(),
            'getDeclaredPropertyDefinitions' => array($propertyDefinition),
            'getDeclaredSupertypes' => array(),
            'getName' => 'node-type-1',
        ));

        $node = $this->getNodeMock(array(
            'getPrimaryNodeType' => $nodeType,
            'getMixinNodeTypes' => array(),
            'getProperties' => array(),
            'getName' => 'node1',
            'getPath' => 'path/to/node',
        ));

        $this->processor->process($node);
    }

    public function testPropertyDefsAutoCreated()
    {
        $jcrUuidProperty = $this->getPropertyDefinitionMock(array(
            'getName' => 'jcr:uuid',
            'isAutoCreated' => true,
            'getRequiredType' => 'String',
        ));
        $jcrCreatedByProperty = $this->getPropertyDefinitionMock(array(
            'getName' => 'jcr:createdBy',
            'isAutoCreated' => true,
        ));
        $jcrModifiedByProperty = $this->getPropertyDefinitionMock(array(
            'getName' => 'jcr:lastModifiedBy',
            'isAutoCreated' => true,
        ));
        $jcrCreatedProperty = $this->getPropertyDefinitionMock(array(
            'getName' => 'jcr:created',
            'isAutoCreated' => true,
        ));
        $jcrLastModifiedProperty = $this->getPropertyDefinitionMock(array(
            'getName' => 'jcr:lastModified',
            'isAutoCreated' => true,
        ));
        $jcrETagProperty = $this->getPropertyDefinitionMock(array(
            'getName' => 'jcr:etag',
            'isAutoCreated' => true,
        ));

        $userPropertySingle = $this->getPropertyDefinitionMock(array(
            'getName' => 'dtl:single',
            'isAutoCreated' => true,
            'getDefaultValues' => array('one', 'two')
        ));
        $userPropertyMultiple = $this->getPropertyDefinitionMock(array(
            'getName' => 'dtl:multiple',
            'isAutoCreated' => true,
            'isMultiple' => true,
            'getDefaultValues' => array('one', 'two')
        ));

        $nodeType = $this->getNodeTypeMock(array(
            'getDeclaredChildNodeDefinitions' => array(),
            'getDeclaredPropertyDefinitions' => array(
                $jcrUuidProperty,
                $jcrCreatedByProperty,
                $jcrModifiedByProperty,
                $jcrCreatedProperty,
                $jcrLastModifiedProperty,
                $jcrETagProperty,
                $userPropertySingle,
                $userPropertyMultiple,
            ),
            'getDeclaredSupertypes' => array(),
            'getName' => 'node-type-1',
        ));

        $node = $this->getNodeMock(array(
            'getPrimaryNodeType' => $nodeType,
            'getMixinNodeTypes' => array(),
            'getProperties' => array(),
        ));

        // expectations
        $node->expects($this->any())
            ->method('setProperty')
            ->withConsecutive(
                array('jcr:uuid', $this->anything(), 'String'),
                array('jcr:createdBy', 'dtl', null),
                array('jcr:lastModifiedBy', 'dtl', null),
                array('jcr:created', $this->anything(), null),
                array('jcr:lastModified', $this->anything(), null),
                array('jcr:etag', 'TODO: generate from binary properties of this node', null),
                array('dtl:single', 'one', null),
                array('dtl:multiple', array('one', 'two'), null)
            );

        $this->processor->process($node);
    }

    /**
     * @expectedException \PHPCR\RepositoryException
     * @expectedExceptionMessage No default value for autocreated property
     */
    public function testPropertyAutoCreatedNoDefaults()
    {
        $userPropertySingle = $this->getPropertyDefinitionMock(array(
            'getName' => 'dtl:single',
            'isAutoCreated' => true,
            'getDefaultValues' => array()
        ));

        $nodeType = $this->getNodeTypeMock(array(
            'getDeclaredChildNodeDefinitions' => array(),
            'getDeclaredPropertyDefinitions' => array($userPropertySingle),
            'getDeclaredSupertypes' => array(),
            'getName' => 'node-type-1',
        ));

        $node = $this->getNodeMock(array(
            'getPrimaryNodeType' => $nodeType,
            'getMixinNodeTypes' => array(),
            'getProperties' => array(),
            'getName' => 'node1',
            'getPath' => 'path/to/node',
        ));

        $this->processor->process($node);
    }

    public function testPropertyDefsAutoCreatedUpdate()
    {
        $jcrModifiedByPropertyDefinition = $this->getPropertyDefinitionMock(array(
            'getName' => 'jcr:lastModifiedBy',
            'isAutoCreated' => true,
        ));
        $jcrLastModifiedPropertyDefinition = $this->getPropertyDefinitionMock(array(
            'getName' => 'jcr:lastModified',
            'isAutoCreated' => true,
        ));
        $jcrETagPropertyDefinition = $this->getPropertyDefinitionMock(array(
            'getName' => 'jcr:etag',
            'isAutoCreated' => true,
        ));

        $jcrModifiedByProperty = $this->getPropertyMock();
        $jcrModifiedProperty = $this->getPropertyMock();
        $jcrETagProperty = $this->getPropertyMock();

        $nodeType = $this->getNodeTypeMock(array(
            'getDeclaredChildNodeDefinitions' => array(),
            'getDeclaredPropertyDefinitions' => array(
                $jcrModifiedByPropertyDefinition,
                $jcrLastModifiedPropertyDefinition,
                $jcrETagPropertyDefinition,
            ),
            'getDeclaredSupertypes' => array(),
            'getName' => 'node-type-1',
        ));

        $node = $this->getNodeMock(array(
            'getPrimaryNodeType' => $nodeType,
            'getMixinNodeTypes' => array(),
            'getProperties' => array(),
        ));

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
                array('jcr:lastModifiedBy'),
                array('jcr:lastModified'),
                array('jcr:etag')
            )
            ->will($this->onConsecutiveCalls($jcrModifiedByProperty, $jcrModifiedProperty, $jcrETagProperty));

        $this->processor->process($node);
    }

    public function providePropertyValidation()
    {
        return array(
            array(
                array(
                    'getType' => PropertyType::NAME,
                    'isMultiple' => false,
                    'getValue' => 'hello',
                ),
                null
            ),
            array(
                array(
                    'getType' => PropertyType::NAME,
                    'isMultiple' => false,
                    'getValue' => 'foo:hello',
                    'getPath' => '/path/to',
                ),
                'Invalid value for NAME property type at "/path/to", the namespace prefix "foo" does not exist'
            ),
            array(
                array(
                    'getType' => PropertyType::PATH,
                    'isMultiple' => false,
                    'getValue' => '/path/to/something',
                    'getPath' => '/path/to',
                ),
                null,
            ),
            /*
            array(
                array(
                    'getType' => PropertyType::PATH,
                    'isMultiple' => false,
                    'getValue' => '  pathto££333+_123£³[]/something[&&"£$]',
                    'getPath' => '/path/to',
                ),
                'Value "  pathto££333+_123£³[]/something[&&"£$]" for PATH property at "/path/to" is invalid',
            ),
            */
            array(
                array(
                    'getType' => PropertyType::URI,
                    'isMultiple' => false,
                    'getValue' => 'http://domain.dom',
                    'getPath' => '/path/to',
                ),
                null,
            ),
            array(
                array(
                    'getType' => PropertyType::URI,
                    'isMultiple' => false,
                    'getValue' => 'http://domain.dom  sd',
                    'getPath' => '/path/to',
                ),
                'Invalid value "http://domain.dom  sd" for URI property at "/path/to". Value has to comply with RFC 3986',
            ),
            array(
                array(
                    'getType' => PropertyType::STRING,
                    'isMultiple' => false,
                    'getValue' => 'some string',
                    'getPath' => '/path/to',
                ),
                null
            ),
        );
    }

    /**
     * @dataProvider providePropertyValidation
     */
    public function testPropertyValidation($propertyConfig, $exception = null)
    {
        $property = $this->getPropertyMock($propertyConfig);

        if ($exception) {
            $this->setExpectedException('PHPCR\ValueFormatException', $exception);
        }

        $nodeType = $this->getNodeTypeMock(array(
            'getDeclaredChildNodeDefinitions' => array(),
            'getDeclaredPropertyDefinitions' => array(),
            'getDeclaredSupertypes' => array(),
            'getName' => 'node-type-1',
        ));

        $node = $this->getNodeMock(array(
            'getPrimaryNodeType' => $nodeType,
            'getMixinNodeTypes' => array(),
            'getProperties' => array($property),
            'getName' => 'node1',
            'getPath' => 'path/to/node',
        ));

        $this->processor->process($node);
    }

    public function provideNamespaceValidation()
    {
        return array(
            array('no-namespace', true),
            array('ns:registered-namespace', true),
            array('dtl:registered-namespace', true),
            array('ltd:unkown-namespace', false),
        );
    }

    /**
     * @dataProvider provideNamespaceValidation
     */
    public function testNamespaceValidation($nodeName, $isValid)
    {
        if (false === $isValid) {
            $this->setExpectedException('PHPCR\NamespaceException', 'is not known');
        }

        $nodeType = $this->getNodeTypeMock(array(
            'getDeclaredChildNodeDefinitions' => array(),
            'getDeclaredPropertyDefinitions' => array(),
            'getDeclaredSupertypes' => array(),
            'getName' => 'node-type-1',
        ));
        $node = $this->getNodeMock(array(
            'getPrimaryNodeType' => $nodeType,
            'getMixinNodeTypes' => array(),
            'getProperties' => array(),
            'getName' => $nodeName,
            'getPath' => 'path/to/node',
        ));

        $this->processor->process($node);
    }

    public function providePropertyValidationOutOfRangeCharacters()
    {
        return array(
            array('This is valid too!'.$this->translateCharFromCode('\u0009'), true),
            array('This is valid', true),
            array($this->translateCharFromCode('\uD7FF'), true),
            array('This is on the edge, but valid too.'. $this->translateCharFromCode('\uFFFD'), true),
            array($this->translateCharFromCode('\u10000'), true),
            array($this->translateCharFromCode('\u10FFFF'), true),
            array($this->translateCharFromCode('\u0001'), false),
            array($this->translateCharFromCode('\u0002'), false),
            array($this->translateCharFromCode('\u0003'), false),
            array($this->translateCharFromCode('\u0008'), false),
            array($this->translateCharFromCode('\uFFFF'), false),
        );
    }

    /**
     * @dataProvider providePropertyValidationOutOfRangeCharacters
     */
    public function testPropertyValidationOutOfRangeCharacters($value, $isValid)
    {
        $property = $this->getPropertyMock(array(
            'getType' => PropertyType::STRING,
            'isMultiple' => false,
            'getValue' => $value,
            'getPath' => '/path/to',
        ));

        if (false === $isValid) {
            $this->setExpectedException('PHPCR\ValueFormatException', 'Invalid character detected in value');
        }

        $nodeType = $this->getNodeTypeMock(array(
            'getDeclaredChildNodeDefinitions' => array(),
            'getDeclaredPropertyDefinitions' => array(),
            'getDeclaredSupertypes' => array(),
            'getName' => 'node-type-1',
        ));

        $node = $this->getNodeMock(array(
            'getPrimaryNodeType' => $nodeType,
            'getMixinNodeTypes' => array(),
            'getProperties' => array($property),
            'getName' => 'node1',
            'getPath' => 'path/to/node',
        ));

        $this->processor->process($node);
    }

    private function translateCharFromCode($char)
    {
        return json_decode('"'.$char.'"');
    }
}
