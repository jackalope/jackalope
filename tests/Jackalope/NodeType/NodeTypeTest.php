<?php

namespace Jackalope\NodeType;

use Jackalope\TestCase;

/**
 * Test the Node Type.
 * TODO this needs some cleanup and we should verify if its complete
 * TODO: tests for ItemDefinition, PropertyDefinition and NodeDefinition probably missing or incomplete inside NodeType
 */
class NodeTypeTest extends TestCase
{
    public function testNodeGetSuperTypesMultiple()
    {
        $ntm = $this->getNodeTypeManager();
        $nt = $ntm->getNodeType('nt:configuration');
        $superTypes = $nt->getSupertypes();
        $superTypes2 = $nt->getSupertypes();

        $this->assertEquals($superTypes, $superTypes2);
    }

    public function testNodeTypeMethods()
    {
        $ntm = $this->getNodeTypeManager();
        $nt = $ntm->getNodeType('nt:configuration');
        $superTypes = $nt->getSupertypes();
        $this->assertEquals(array("mix:versionable", "nt:base"), $nt->getDeclaredSupertypeNames());
        $this->assertCount(4, $superTypes);

        $this->assertSame(array(
            $ntm->getNodeType('mix:versionable'),
            $ntm->getNodeType('mix:referenceable'),
            $ntm->getNodeType('mix:simpleVersionable'),
            $ntm->getNodeType('nt:base')),
            $superTypes
        );
        $this->assertSame(array($ntm->getNodeType('mix:versionable'), $ntm->getNodeType('nt:base')),$nt->getDeclaredSupertypes());
        $declaredSubTypes = $nt->getDeclaredSubtypes();
        $this->assertInstanceOf('Iterator', $declaredSubTypes);
        $this->assertCount(0, $declaredSubTypes);
        $subTypes = $nt->getSubtypes();
        $this->assertInstanceOf('Iterator', $subTypes);
        $this->assertCount(0, $subTypes);
        $this->assertTrue($nt->isNodeType('nt:configuration'));
        $this->assertTrue($nt->isNodeType('nt:base'));
        $this->assertTrue($nt->isNodeType('mix:simpleVersionable'));
        $this->assertFalse($nt->isNodeType('notanodetype'));
        $expectedProperties = array('jcr:root', 'jcr:predecessors', 'jcr:configuration', 'jcr:activity', 'jcr:mergeFailed', 'jcr:versionHistory', 'jcr:baseVersion', 'jcr:uuid', 'jcr:isCheckedOut', 'jcr:mixinTypes', 'jcr:primaryType');
        $this->assertCount(count($expectedProperties), $nt->getPropertyDefinitions());
        $i = 0;
        foreach ($nt->getPropertyDefinitions() as $propDef) {
            $this->assertInstanceOf('Jackalope\NodeType\PropertyDefinition', $propDef);
            $this->assertSame($expectedProperties[$i], $propDef->getName());
            $i++;
        }
        $this->assertSame(array(),$nt->getChildNodeDefinitions());

        $nt = $ntm->getNodeType('nt:hierarchyNode');
        $declaredSubTypes = $nt->getDeclaredSubtypes();
        $this->assertInstanceOf('Iterator', $declaredSubTypes);
        $this->assertCount(5, $declaredSubTypes);
        $subnode = $declaredSubTypes->current();
        $this->assertInstanceOf('Jackalope\NodeType\NodeType', $subnode);
        $this->assertSame('nt:file', $subnode->getName());
        $subTypes = $nt->getSubtypes();
        $this->assertInstanceOf('Iterator', $subTypes);
        $this->assertCount(7, $subTypes);
        $subTypes->seek(4);
        $subnode = $subTypes->current();
        $this->assertInstanceOf('Jackalope\NodeType\NodeType', $subnode);
        $this->assertSame('rep:Group', $subnode->getName());

        $nt = $ntm->getNodeType('rep:PrincipalAccessControl');
        $expectedChildNodes = array('rep:policy', '*', '*');
        $this->assertCount(count($expectedChildNodes), $nt->getChildNodeDefinitions());
        $i = 0;
        foreach ($nt->getChildNodeDefinitions() as $childNode) {
            $this->assertInstanceOf('Jackalope\NodeType\NodeDefinition', $childNode);
            $this->assertSame($expectedChildNodes[$i], $childNode->getName());
            $i++;
        }
    }

    public function testGetDefinedChildNodesAndNodeDefinitions()
    {
        $ntm = $this->getNodeTypeManager();
        $nt = $ntm->getNodeType('nt:folder');
        $nodes = $nt->getDeclaredChildNodeDefinitions();
        $this->assertInternalType('array', $nodes);
        $this->assertCount(1, $nodes);
        $node = $nodes[0];
        $this->assertInstanceOf('Jackalope\NodeType\NodeDefinition', $node);
        $this->assertSame('*', $node->getName());
        $this->assertSame(array($ntm->getNodeType('nt:hierarchyNode')), $node->getRequiredPrimaryTypes());
        $this->assertSame(array('nt:hierarchyNode'), $node->getRequiredPrimaryTypeNames());
        $this->assertSame(null, $node->getDefaultPrimaryTypeName());
        $this->assertSame(null, $node->getDefaultPrimaryType());
        $this->assertSame(false, $node->allowsSameNameSiblings());

        $nt = $ntm->getNodeType('nt:file');
        $nodes = $nt->getDeclaredChildNodeDefinitions();
        $this->assertInternalType('array', $nodes);
        $this->assertCount(1, $nodes);
        $node = $nodes[0];
        $this->assertInstanceOf('Jackalope\NodeType\NodeDefinition', $node);
        $this->assertSame('jcr:content', $node->getName());
        $this->assertSame(array($ntm->getNodeType('nt:base'), $ntm->getNodeType('nt:folder')), $node->getRequiredPrimaryTypes());
        $this->assertSame(array('nt:base', 'nt:folder'), $node->getRequiredPrimaryTypeNames());
        $this->assertSame(null, $node->getDefaultPrimaryTypeName());
        $this->assertSame(null, $node->getDefaultPrimaryType());

        //Test defaultPrimaryType
        $nt = $ntm->getNodeType('nt:nodeType');
        $nodes = $nt->getDeclaredChildNodeDefinitions();
        $this->assertCount(2, $nodes);
        $node = $nodes[0];
        $this->assertSame('nt:childNodeDefinition', $node->getDefaultPrimaryTypeName());
        $this->assertSame($ntm->getNodeType('nt:childNodeDefinition'), $node->getDefaultPrimaryType());
        $this->assertTrue($node->allowsSameNameSiblings());
    }

    public function testGetDefinedPropertysAndPropertyDefinition()
    {
        $ntm = $this->getNodeTypeManager();
        $nt = $ntm->getNodeType('nt:file');
        $properties = $nt->getDeclaredPropertyDefinitions();
        $this->assertInternalType('array', $properties);
        $this->assertCount(0, $properties);

        $nt = $ntm->getNodeType('mix:created');
        $this->assertInstanceOf('Jackalope\NodeType\NodeType', $nt);
        $this->assertSame('mix:created', $nt->getName());
        $this->assertSame(array(), $nt->getDeclaredSupertypeNames());
        $this->assertFalse($nt->isAbstract());
        $this->assertTrue($nt->isMixin());
        $this->assertFalse($nt->hasOrderableChildNodes());
        $this->assertTrue($nt->isQueryable());
        $this->assertNull($nt->getPrimaryItemName());

        //ItemDefinition
        $properties = $nt->getDeclaredPropertyDefinitions();
        $this->assertInternalType('array', $properties);
        $this->assertCount(2, $properties);
        $property = $properties[0];
        $this->assertSame($nt, $property->getDeclaringNodeType());
        $this->assertSame('jcr:createdBy',$property->getName());
        $this->assertTrue($property->isAutoCreated());
        $this->assertFalse($property->isMandatory());
        $this->assertSame(\PHPCR\Version\OnParentVersionAction::COPY,$property->getOnParentVersion());
        $this->assertTrue($property->isProtected());
        $this->assertSame(array(),$property->getDefaultValues());

        //PropertyDefinition
        $this->assertSame(\PHPCR\PropertyType::STRING, $property->getRequiredType());
        $this->assertSame(array(), $property->getValueConstraints());
        $this->assertFalse($property->isMultiple());
        $this->assertSame(array('jcr.operator.equal.to', 'jcr.operator.not.equal.to', 'jcr.operator.greater.than', 'jcr.operator.greater.than.or.equal.to', 'jcr.operator.less.than', 'jcr.operator.less.than.or.equal.to', 'jcr.operator.like'), $property->getAvailableQueryOperators());
        $this->assertTrue($property->isFullTextSearchable());
        $this->assertTrue($property->isQueryOrderable());

        $nt = $ntm->getNodeType('mix:versionable');
        $properties = $nt->getDeclaredPropertyDefinitions();
        $property = $properties[0];
        $this->assertSame(array('nt:version'), $property->getValueConstraints());

        $nt = $ntm->getNodeType('mix:simpleVersionable');
        $properties = $nt->getDeclaredPropertyDefinitions();
        $property = $properties[0];
        $defaultValues = $property->getDefaultValues();
        $this->assertCount(1, $defaultValues);
        $this->assertSame('true', $defaultValues[0]);
    }
}
