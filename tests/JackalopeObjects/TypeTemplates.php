<?php
namespace jackalope\tests\JackalopeObjects;

use \PHPUnit_Framework_Constraint_IsType;

require_once(dirname(__FILE__) . '/../inc/JackalopeObjectsCase.php');

/**
 * @covers: NodeTypeTemplate, NodeDefinitionTemplate PropertyDefinitionTemplate
 */
class TypeTemplates extends \jackalope\JackalopeObjectsCase {
    /**
     * @covers jackalope\NodeType\NodeTypeTemplate
     */
    public function testCreateNodeTypeTemplateEmpty() {
        $ntm = $this->getNodeTypeManager();

        $ntt = $ntm->createNodeTypeTemplate();

        // is empty as defined by doc
        $this->assertNull($ntt->getName());
        $this->assertEquals(array('nt:base'), $ntt->getDeclaredSupertypeNames());
        $this->assertFalse($ntt->isAbstract());
        $this->assertFalse($ntt->isMixin());
        $this->assertFalse($ntt->hasOrderableChildNodes());
        $this->assertFalse($ntt->isQueryable());
        $this->assertNull($ntt->getPrimaryItemName());
        $this->assertNull($ntt->getDeclaredPropertyDefinitions());
        $this->assertNull($ntt->getDeclaredChildNodeDefinitions());
    }

    /**
     * @covers jackalope\NodeType\NodeDefinitionTemplate::__construct
     */
    public function testCreateNodeDefinitionTemplateEmpty() {
        $ntm = $this->getNodeTypeManager();

        $ndt = $ntm->createNodeDefinitionTemplate();

        // is empty as defined by doc
        $this->assertNull($ndt->getName());
        $this->assertFalse($ndt->isAutoCreated());
        $this->assertFalse($ndt->isMandatory());
        $this->assertSame(\PHPCR\Version\OnParentVersionAction::COPY, $ndt->getOnParentVersion());
        $this->assertFalse($ndt->isProtected());
        $this->assertNull($ndt->getRequiredPrimaryTypeNames());
        $this->assertNull($ndt->getDefaultPrimaryTypeName());
        $this->assertFalse($ndt->allowsSameNameSiblings());
    }

    /**
     * @covers jackalope\NodeType\PropertyDefinitionTemplate::__construct
     */
    public function testCreatePropertyDefinitionTemplateEmpty() {
        $ntm = $this->getNodeTypeManager();

        $ndt = $ntm->createPropertyDefinitionTemplate();

        // is empty as defined by doc
        $this->assertNull($ndt->getName());
        $this->assertFalse($ndt->isAutoCreated());
        $this->assertFalse($ndt->isMandatory());
        $this->assertSame(\PHPCR\Version\OnParentVersionAction::COPY, $ndt->getOnParentVersion());
        $this->assertFalse($ndt->isProtected());
        $this->assertSame(\PHPCR\PropertyType::STRING, $ndt->getRequiredType());
        $this->assertNull($ndt->getValueConstraints());
        $this->assertNull($ndt->getDefaultValues());
        $this->assertFalse($ndt->isMultiple());
        $this->assertFalse($ndt->isFullTextSearchable());
        $this->assertFalse($ndt->isQueryOrderable());
    }
}
