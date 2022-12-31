<?php

namespace Jackalope\NodeType;

use Jackalope\Factory;
use Jackalope\TestCase;

class NodeTypeXmlConverterTest extends TestCase
{
    private $converter;

    public function setUp(): void
    {
        $factory = new Factory();
        $this->converter = new NodeTypeXmlConverter($factory);
    }

    public function testConvertNtBase()
    {
        $data = $this->converter->getNodeTypeDefinitionFromXml($this->getNodeTypeDOMElement('nt:base'));

        $this->assertEquals([
            'name' => 'nt:base',
            'isAbstract' => true,
            'isMixin' => false,
            'isQueryable' => true,
            'hasOrderableChildNodes' => true,
            'primaryItemName' => null,
            'declaredSuperTypeNames' => [],
            'declaredPropertyDefinitions' => [
                [
                    'declaringNodeType' => '',
                    'name' => 'jcr:primaryType',
                    'isAutoCreated' => true,
                    'isMandatory' => true,
                    'isProtected' => true,
                    'onParentVersion' => 4,
                    'requiredType' => 7,
                    'multiple' => false,
                    'fullTextSearchable' => true,
                    'queryOrderable' => true,
                ],
                [
                    'declaringNodeType' => '',
                    'name' => 'jcr:mixinTypes',
                    'isAutoCreated' => true,
                    'isMandatory' => true,
                    'isProtected' => true,
                    'onParentVersion' => 4,
                    'requiredType' => 7,
                    'multiple' => true,
                    'fullTextSearchable' => true,
                    'queryOrderable' => true,
                ],
            ],
            'declaredNodeDefinitions' => [],
        ], $data);
    }

    public function testConvertNtUnstructured()
    {
        $data = $this->converter->getNodeTypeDefinitionFromXml($this->getNodeTypeDOMElement('nt:unstructured'));

        $this->assertEquals([
            'name' => 'nt:unstructured',
            'isAbstract' => false,
            'isMixin' => false,
            'isQueryable' => true,
            'hasOrderableChildNodes' => true,
            'primaryItemName' => null,
            'declaredSuperTypeNames' => ['nt:base'],
            'declaredPropertyDefinitions' => [
                [
                    'declaringNodeType' => 'nt:unstructured',
                    'name' => '*',
                    'isAutoCreated' => false,
                    'isMandatory' => false,
                    'isProtected' => false,
                    'onParentVersion' => 1,
                    'requiredType' => 0,
                    'multiple' => true,
                    'fullTextSearchable' => true,
                    'queryOrderable' => true,
                ],
            ],
            'declaredNodeDefinitions' => [
                [
                    'declaringNodeType' => 'nt:unstructured',
                    'name' => '*',
                    'isAutoCreated' => false,
                    'isMandatory' => false,
                    'isProtected' => false,
                    'onParentVersion' => 2,
                    'allowsSameNameSiblings' => false,
                    'defaultPrimaryTypeName' => 'nt:unstructured',
                    'requiredPrimaryTypeNames' => ['nt:base'],
                ],
            ],
        ], $data);
    }

    public function getNodeTypeDOMElement($name)
    {
        $xml = <<<XML
<nodeTypes>
    <nodeType hasOrderableChildNodes="true" isQueryable="true" name="nt:base" isMixin="false" isAbstract="true">
        <propertyDefinition name="jcr:primaryType" requiredType="NAME" autoCreated="true" mandatory="true" protected="true" multiple="false" fullTextSearchable="true" queryOrderable="true" onParentVersion="COMPUTE" />
        <propertyDefinition name="jcr:mixinTypes" requiredType="NAME" autoCreated="true" mandatory="true" protected="true" multiple="true" fullTextSearchable="true" queryOrderable="true" onParentVersion="COMPUTE" />
    </nodeType>
    <nodeType hasOrderableChildNodes="true" isQueryable="true" name="nt:unstructured" isMixin="false" isAbstract="false">
        <supertypes>
            <supertype>nt:base</supertype>
        </supertypes>
        <childNodeDefinition autoCreated="false" declaringNodeType="nt:unstructured" defaultPrimaryType="nt:unstructured" mandatory="false" name="*" onParentVersion="VERSION" protected="false" sameNameSiblings="false">
          <requiredPrimaryTypes>
            <requiredPrimaryType>nt:base</requiredPrimaryType>
          </requiredPrimaryTypes>
        </childNodeDefinition>
        <propertyDefinition autoCreated="false" declaringNodeType="nt:unstructured" fullTextSearchable="true" mandatory="false" multiple="true" name="*" onParentVersion="COPY" protected="false" queryOrderable="true" requiredType="undefined" />
    </nodeType>
    <nodeType hasOrderableChildNodes="false" isQueryable="false" name="mix:etag" isMixin="true">
        <propertyDefinition name="jcr:etag" requiredType="STRING" autoCreated="true" protected="true" onParentVersion="COMPUTE" />
    </nodeType>
    <nodeType name="nt:hierachy" isAbstract="true">
        <supertypes>
            <supertype>mix:created</supertype>
        </supertypes>
    </nodeType>
    <nodeType name="nt:file" isMixin="false" isAbstract="false">
        <supertypes>
            <supertype>nt:hierachy</supertype>
        </supertypes>
    </nodeType>
    <nodeType name="nt:folder" isMixin="false" isAbstract="false">
        <supertypes>
            <supertype>nt:hierachy</supertype>
        </supertypes>
    </nodeType>
    <nodeType name="nt:resource" isMixin="false" isAbstract="false" primaryItemName="jcr:data">
        <supertypes>
            <supertype>mix:mimeType</supertype>
            <supertype>mix:modified</supertype>
        </supertypes>
        <propertyDefinition name="jcr:created" requiredType="BINARY" autoCreated="false" protected="false" onParentVersion="COPY" />
    </nodeType>
    <nodeType name="mix:created" isMixin="true">
        <propertyDefinition name="jcr:created" requiredType="DATE" autoCreated="true" protected="true" onParentVersion="COMPUTE" />
        <propertyDefinition name="jcr:createdBy" requiredType="STRING" autoCreated="true" protected="true" onParentVersion="COMPUTE" />
    </nodeType>
    <nodeType name="mix:mimeType" isMixin="true">
        <propertyDefinition name="jcr:mimeType" requiredType="DATE" autoCreated="false" protected="true" onParentVersion="COPY" />
        <propertyDefinition name="jcr:encoding" requiredType="STRING" autoCreated="false" protected="true" onParentVersion="COPY" />
    </nodeType>
    <nodeType name="mix:lastModified" isMixin="true">
        <propertyDefinition name="jcr:lastModified" requiredType="DATE" autoCreated="true" protected="true" onParentVersion="COMPUTE" />
        <propertyDefinition name="jcr:lastModifiedBy" requiredType="STRING" autoCreated="true" protected="true" onParentVersion="COMPUTE" />
    </nodeType>
</nodeTypes>

XML;
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($xml);

        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->evaluate('//nodeTypes/nodeType[@name="'.$name.'"]');
        if (1 != $nodes->length) {
            $this->fail("Should have found exactly one element <nodeType> with name $name");
        }

        return $nodes->item(0);
    }
}
