<?php

namespace Jackalope\NodeType;

use Jackalope\TestCase;

class NodeTypeDefinitionTest extends TestCase
{
    /**
     * @expectedException   \InvalidArgumentException
     */
    public function testCtorInvalidNodeTypeDefinition()
    {
        $this->getNodeTypeManager()->createNodeTypeTemplate(new \stdclass);
    }

    public function testCreateFromArray()
    {
        $factory = $this->getMock('Jackalope\Factory');
        $nodeTypeManager = $this->getMock('Jackalope\NodeType\NodeTypeManager', array(), array(), '', false);
        $typeDef = new NodeTypeDefinition($factory, $nodeTypeManager, array(
            'name'                      => 'test',
            'isAbstract'                => true,
            'isMixin'                   => true,
            'isQueryable'               => true,
            'hasOrderableChildNodes'    => true,
            'primaryItemName'           => 'foo',
            'supertypes'                => array(),
            'declaredPropertyDefinitions' => array(),
            'declaredNodeDefinitions'   => array(),
        ));

        $this->assertEquals('test', $typeDef->getName());
        $this->assertTrue($typeDef->isAbstract());
        $this->assertTrue($typeDef->isMixin());
        $this->assertTrue($typeDef->isQueryable());
        $this->assertTrue($typeDef->hasOrderableChildNodes());
        $this->assertEquals('foo', $typeDef->getPrimaryItemName());
        $this->assertEquals(array(), $typeDef->getDeclaredSupertypeNames(), "Supertypes should be empty");
    }

    public function testCreateFromArrayFalse()
    {
        $factory = $this->getMock('Jackalope\Factory');
        $nodeTypeManager = $this->getMock('Jackalope\NodeType\NodeTypeManager', array(), array(), '', false);
        $typeDef = new NodeTypeDefinition($factory, $nodeTypeManager, array(
            'name'                      => 'test',
            'isAbstract'                => false,
            'isMixin'                   => false,
            'isQueryable'               => false,
            'hasOrderableChildNodes'    => false,
            'primaryItemName'           => 'foo',
            'supertypes'                => array(),
            'declaredPropertyDefinitions' => array(),
            'declaredNodeDefinitions'   => array(),
        ));

        $this->assertFalse($typeDef->isAbstract());
        $this->assertFalse($typeDef->isMixin());
        $this->assertFalse($typeDef->isQueryable());
        $this->assertFalse($typeDef->hasOrderableChildNodes());
    }

    public function testCreateFromXmlNtBase()
    {
        $factory = $this->getMock('Jackalope\Factory');
        $nodeTypeManager = $this->getMock('Jackalope\NodeType\NodeTypeManager', array(), array(), '', false);
        $typeDef = new NodeTypeDefinition($factory, $nodeTypeManager, $this->getNodeTypeDOMElement('nt:base'));

        $this->assertEquals('nt:base', $typeDef->getName());
        $this->assertFalse($typeDef->isMixin());
        $this->assertTrue($typeDef->isAbstract());
        $this->assertTrue($typeDef->isQueryable());
        $this->assertEquals(2, count($typeDef->getDeclaredPropertyDefinitions()));
        $this->assertEquals(0, count($typeDef->getDeclaredChildNodeDefinitions()));
    }

    public function testCreateFromXmlNtUnstructured()
    {
        $factory = $this->getMock('Jackalope\Factory');
        $nodeTypeManager = $this->getMock('Jackalope\NodeType\NodeTypeManager', array(), array(), '', false);
        $typeDef = new NodeTypeDefinition($factory, $nodeTypeManager, $this->getNodeTypeDOMElement('nt:unstructured'));

        $this->assertEquals('nt:unstructured', $typeDef->getName());
        $this->assertFalse($typeDef->isMixin());
        $this->assertFalse($typeDef->isAbstract());
        $this->assertTrue($typeDef->isQueryable());
        $this->assertEquals(array('nt:base'), $typeDef->getDeclaredSupertypeNames());
    }

    public function getNodeTypeDOMElement($name)
    {
        $xml = <<<XML
<nodeTypes>
    <nodeType name="nt:base" isMixin="false" isAbstract="true">
        <propertyDefinition name="jcr:primaryType" requiredType="NAME" autoCreated="true" mandatory="true" protected="true" onParentVersion="COMPUTE" />
        <propertyDefinition name="jcr:mixinTypes" requiredType="NAME" autoCreated="true" mandatory="true" protected="true" multiple="true" onParentVersion="COMPUTE" />
    </nodeType>
    <nodeType name="nt:unstructured" hasOrderableChildNodes="true" isMixin="false" isAbstract="false">
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
    <nodeType name="mix:etag" isMixin="true">
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

          $xpath = new \DOMXpath($dom);
          $nodes = $xpath->evaluate('//nodeTypes/nodeType[@name="'.$name.'"]');
          if ($nodes->length != 1) {
              $this->fail("Should have found exactly one element <nodeType> with name " . $name);
          }
          return $nodes->item(0);
    }
}
