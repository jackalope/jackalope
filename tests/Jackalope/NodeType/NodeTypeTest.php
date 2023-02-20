<?php

namespace Jackalope\NodeType;

use Jackalope\TestCase;
use PHPCR\PropertyType;
use PHPCR\Version\OnParentVersionAction;

/**
 * Test the Node Type.
 * TODO this needs some cleanup and we should verify if its complete
 * TODO: tests for ItemDefinition, PropertyDefinition and NodeDefinition probably missing or incomplete inside NodeType.
 */
class NodeTypeTest extends TestCase
{
    public function testNodeGetSuperTypesMultiple(): void
    {
        $ntm = $this->getNodeTypeManager();
        $nt = $ntm->getNodeType('nt:configuration');
        $superTypes = $nt->getSupertypes();
        $superTypes2 = $nt->getSupertypes();

        $this->assertEquals($superTypes, $superTypes2);
    }

    public function testNodeTypeMethods(): void
    {
        $ntm = $this->getNodeTypeManager();
        $nt = $ntm->getNodeType('nt:configuration');
        $superTypes = $nt->getSupertypes();
        $this->assertEquals(['mix:versionable', 'nt:base'], $nt->getDeclaredSupertypeNames());
        $this->assertCount(4, $superTypes);

        $this->assertSame(
            [
                $ntm->getNodeType('mix:versionable'),
                $ntm->getNodeType('mix:referenceable'),
                $ntm->getNodeType('mix:simpleVersionable'),
                $ntm->getNodeType('nt:base'),
            ],
            $superTypes
        );

        $this->assertSame([$ntm->getNodeType('mix:versionable'), $ntm->getNodeType('nt:base')], $nt->getDeclaredSupertypes());
        $declaredSubTypes = $nt->getDeclaredSubtypes();
        $this->assertInstanceOf(\Iterator::class, $declaredSubTypes);
        $this->assertCount(0, $declaredSubTypes);
        $subTypes = $nt->getSubtypes();
        $this->assertInstanceOf(\Iterator::class, $subTypes);
        $this->assertCount(0, $subTypes);
        $this->assertTrue($nt->isNodeType('nt:configuration'));
        $this->assertTrue($nt->isNodeType('nt:base'));
        $this->assertTrue($nt->isNodeType('mix:simpleVersionable'));
        $this->assertFalse($nt->isNodeType('notanodetype'));
        $expectedProperties = ['jcr:root', 'jcr:predecessors', 'jcr:configuration', 'jcr:activity', 'jcr:mergeFailed', 'jcr:versionHistory', 'jcr:baseVersion', 'jcr:uuid', 'jcr:isCheckedOut', 'jcr:mixinTypes', 'jcr:primaryType'];
        $this->assertCount(count($expectedProperties), $nt->getPropertyDefinitions());
        $i = 0;
        foreach ($nt->getPropertyDefinitions() as $propDef) {
            $this->assertInstanceOf(PropertyDefinition::class, $propDef);
            $this->assertSame($expectedProperties[$i], $propDef->getName());
            ++$i;
        }
        $this->assertSame([], $nt->getChildNodeDefinitions());

        $nt = $ntm->getNodeType('nt:hierarchyNode');
        $declaredSubTypes = $nt->getDeclaredSubtypes();
        $this->assertInstanceOf(\Iterator::class, $declaredSubTypes);
        $this->assertCount(5, $declaredSubTypes);
        $subnode = $declaredSubTypes->current();
        $this->assertInstanceOf(NodeType::class, $subnode);
        $this->assertSame('nt:file', $subnode->getName());
        $subTypes = $nt->getSubtypes();
        $this->assertInstanceOf(\Iterator::class, $subTypes);
        $this->assertCount(7, $subTypes);
        $subTypes->seek(4);
        $subnode = $subTypes->current();
        $this->assertInstanceOf(NodeType::class, $subnode);
        $this->assertSame('rep:Group', $subnode->getName());

        $nt = $ntm->getNodeType('rep:PrincipalAccessControl');
        $expectedChildNodes = ['rep:policy', '*', '*'];
        $this->assertCount(count($expectedChildNodes), $nt->getChildNodeDefinitions());
        $i = 0;
        foreach ($nt->getChildNodeDefinitions() as $childNode) {
            $this->assertInstanceOf(NodeDefinition::class, $childNode);
            $this->assertSame($expectedChildNodes[$i], $childNode->getName());
            ++$i;
        }
    }

    public function testGetDefinedChildNodesAndNodeDefinitions(): void
    {
        $ntm = $this->getNodeTypeManager();
        $nt = $ntm->getNodeType('nt:folder');
        $nodes = $nt->getDeclaredChildNodeDefinitions();
        $this->assertIsArray($nodes);
        $this->assertCount(1, $nodes);
        $node = $nodes[0];
        $this->assertInstanceOf(NodeDefinition::class, $node);
        $this->assertSame('*', $node->getName());
        $this->assertSame([$ntm->getNodeType('nt:hierarchyNode')], $node->getRequiredPrimaryTypes());
        $this->assertSame(['nt:hierarchyNode'], $node->getRequiredPrimaryTypeNames());
        $this->assertNull($node->getDefaultPrimaryTypeName());
        $this->assertNull($node->getDefaultPrimaryType());
        $this->assertFalse($node->allowsSameNameSiblings());

        $nt = $ntm->getNodeType('nt:file');
        $nodes = $nt->getDeclaredChildNodeDefinitions();
        $this->assertIsArray($nodes);
        $this->assertCount(1, $nodes);
        $node = $nodes[0];
        $this->assertInstanceOf(NodeDefinition::class, $node);
        $this->assertSame('jcr:content', $node->getName());
        $this->assertSame([$ntm->getNodeType('nt:base'), $ntm->getNodeType('nt:folder')], $node->getRequiredPrimaryTypes());
        $this->assertSame(['nt:base', 'nt:folder'], $node->getRequiredPrimaryTypeNames());
        $this->assertNull($node->getDefaultPrimaryTypeName());
        $this->assertNull($node->getDefaultPrimaryType());

        // Test defaultPrimaryType
        $nt = $ntm->getNodeType('nt:nodeType');
        $nodes = $nt->getDeclaredChildNodeDefinitions();
        $this->assertCount(2, $nodes);
        $node = $nodes[0];
        $this->assertSame('nt:childNodeDefinition', $node->getDefaultPrimaryTypeName());
        $this->assertSame($ntm->getNodeType('nt:childNodeDefinition'), $node->getDefaultPrimaryType());
        $this->assertTrue($node->allowsSameNameSiblings());
    }

    public function testGetDefinedPropertysAndPropertyDefinition(): void
    {
        $ntm = $this->getNodeTypeManager();
        $nt = $ntm->getNodeType('nt:file');
        $properties = $nt->getDeclaredPropertyDefinitions();
        $this->assertIsArray($properties);
        $this->assertCount(0, $properties);

        $nt = $ntm->getNodeType('mix:created');
        $this->assertInstanceOf(NodeType::class, $nt);
        $this->assertSame('mix:created', $nt->getName());
        $this->assertSame([], $nt->getDeclaredSupertypeNames());
        $this->assertFalse($nt->isAbstract());
        $this->assertTrue($nt->isMixin());
        $this->assertFalse($nt->hasOrderableChildNodes());
        $this->assertTrue($nt->isQueryable());
        $this->assertNull($nt->getPrimaryItemName());

        // ItemDefinition
        $properties = $nt->getDeclaredPropertyDefinitions();
        $this->assertIsArray($properties);
        $this->assertCount(2, $properties);
        $property = $properties[0];
        $this->assertSame($nt, $property->getDeclaringNodeType());
        $this->assertSame('jcr:createdBy', $property->getName());
        $this->assertTrue($property->isAutoCreated());
        $this->assertFalse($property->isMandatory());
        $this->assertSame(OnParentVersionAction::COPY, $property->getOnParentVersion());
        $this->assertTrue($property->isProtected());
        $this->assertSame([], $property->getDefaultValues());

        // PropertyDefinition
        $this->assertSame(PropertyType::STRING, $property->getRequiredType());
        $this->assertSame([], $property->getValueConstraints());
        $this->assertFalse($property->isMultiple());
        $this->assertSame(['jcr.operator.equal.to', 'jcr.operator.not.equal.to', 'jcr.operator.greater.than', 'jcr.operator.greater.than.or.equal.to', 'jcr.operator.less.than', 'jcr.operator.less.than.or.equal.to', 'jcr.operator.like'], $property->getAvailableQueryOperators());
        $this->assertTrue($property->isFullTextSearchable());
        $this->assertTrue($property->isQueryOrderable());

        $nt = $ntm->getNodeType('mix:versionable');
        $properties = $nt->getDeclaredPropertyDefinitions();
        $property = $properties[0];
        $this->assertSame(['nt:version'], $property->getValueConstraints());

        $nt = $ntm->getNodeType('mix:simpleVersionable');
        $properties = $nt->getDeclaredPropertyDefinitions();
        $property = $properties[0];
        $defaultValues = $property->getDefaultValues();
        $this->assertCount(1, $defaultValues);
        $this->assertSame('true', $defaultValues[0]);
    }
}
