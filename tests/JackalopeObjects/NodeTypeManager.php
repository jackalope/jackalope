<?php
require_once(dirname(__FILE__) . '/../inc/JackalopeObjectsCase.php');

class jackalope_tests_NodeTypeManager extends jackalope_JackalopeObjectsCase {
    //This tests NodeType and NodeTypeDefinition as well
    public function testGetNodeType() {
        $ntm = $this->getNodeTypeManager();
        $nt = $ntm->getNodeType('nt:file');
        $this->assertType('jackalope_NodeType_NodeType', $nt);
        $this->assertSame('nt:file', $nt->getName());
        $this->assertSame(array('nt:hierarchyNode'), $nt->getDeclaredSupertypeNames());
        $this->assertSame(false, $nt->isAbstract());
        $this->assertSame(false, $nt->isMixin());
        $this->assertSame(false, $nt->hasOrderableChildNodes());
        $this->assertSame(true, $nt->isQueryable());
        $this->assertSame('jcr:content', $nt->getPrimaryItemName());
    }
    
    public function testNodeTypeMethods() {
        $ntm = $this->getNodeTypeManager();
        $nt = $ntm->getNodeType('nt:configuration');
        $this->assertSame(array($ntm->getNodeType('mix:versionable'),$ntm->getNodeType('mix:referenceable'),$ntm->getNodeType('mix:simpleVersionable'), $ntm->getNodeType('nt:base')),$nt->getSupertypes());
        $this->assertSame(array($ntm->getNodeType('mix:versionable'), $ntm->getNodeType('nt:base')),$nt->getDeclaredSupertypes());
        // $this->assertSame(,$nt->getSubtypes());
        // $this->assertSame(,$nt->getDeclaredSubtypes());
        $this->assertSame(true,$nt->isNodeType('nt:configuration'));
        $this->assertSame(true,$nt->isNodeType('nt:base'));
        $this->assertSame(true,$nt->isNodeType('mix:simpleVersionable'));
        $this->assertSame(false,$nt->isNodeType('notanodetype'));
        // $this->assertSame(,$nt->getPropertyDefinitions());
        // $this->assertSame(,$nt->getChildNodeDefinitions());
        // $this->assertSame(,$nt->canSetProperty());
        // $this->assertSame(,$nt->canAddChildNode());
        // $this->assertSame(,$nt->canRemoveNode());
        // $this->assertSame(,$nt->canRemoveProperty());
        
        $nt = $ntm->getNodeType('mix:created');
        // $this->assertSame(array(), $nt->getSupertypes());
    }
    
    public function testGetDefinedChildNodesAndNodeDefinitions() {
        $ntm = $this->getNodeTypeManager();
        $nt = $ntm->getNodeType('nt:folder');
        $nodes = $nt->getDeclaredChildNodeDefinitions();
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $nodes);
        $this->assertEquals(1, count($nodes));
        $node = $nodes[0];
        $this->assertType('jackalope_NodeType_NodeDefinition', $node);
        $this->assertSame('*', $node->getName());
        $this->assertSame(array($ntm->getNodeType('nt:hierarchyNode')), $node->getRequiredPrimaryTypes());
        $this->assertSame(array('nt:hierarchyNode'), $node->getRequiredPrimaryTypeNames());
        $this->assertSame(null, $node->getDefaultPrimaryTypeName());
        $this->assertSame(null, $node->getDefaultPrimaryType());
        $this->assertSame(false, $node->allowsSameNameSiblings());
        
        $ntm = $this->getNodeTypeManager();
        $nt = $ntm->getNodeType('nt:file');
        $nodes = $nt->getDeclaredChildNodeDefinitions();
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $nodes);
        $this->assertEquals(1, count($nodes));
        $node = $nodes[0];
        $this->assertType('jackalope_NodeType_NodeDefinition', $node);
        $this->assertSame('jcr:content', $node->getName());
        $this->assertSame(array($ntm->getNodeType('nt:base'), $ntm->getNodeType('nt:folder')), $node->getRequiredPrimaryTypes());
        $this->assertSame(array('nt:base', 'nt:folder'), $node->getRequiredPrimaryTypeNames());
        $this->assertSame(null, $node->getDefaultPrimaryTypeName());
        $this->assertSame(null, $node->getDefaultPrimaryType());
        
        //Test defaultPrimaryType
        $ntm = $this->getNodeTypeManager();
        $nt = $ntm->getNodeType('nt:nodeType');
        $nodes = $nt->getDeclaredChildNodeDefinitions();
        $this->assertEquals(2, count($nodes));
        $node = $nodes[0];
        $this->assertSame('nt:childNodeDefinition', $node->getDefaultPrimaryTypeName());
        $this->assertSame($ntm->getNodeType('nt:childNodeDefinition'), $node->getDefaultPrimaryType());
        $this->assertSame(true, $node->allowsSameNameSiblings());
    }
    
    public function testGetDefinedPropertysAndPropertyDefinition() {
        $ntm = $this->getNodeTypeManager();
        $nt = $ntm->getNodeType('nt:file');
        $properties = $nt->getDeclaredPropertyDefinitions();
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $properties);
        $this->assertEquals(0, count($properties));
        
        $nt = $ntm->getNodeType('mix:created');
        $this->assertType('jackalope_NodeType_NodeType', $nt);
        $this->assertSame('mix:created', $nt->getName());
        $this->assertSame(array(), $nt->getDeclaredSupertypeNames());
        $this->assertSame(false, $nt->isAbstract());
        $this->assertSame(true, $nt->isMixin());
        $this->assertSame(false, $nt->hasOrderableChildNodes());
        $this->assertSame(true, $nt->isQueryable());
        $this->assertSame(null, $nt->getPrimaryItemName());
        
        //ItemDefinition
        $properties = $nt->getDeclaredPropertyDefinitions();
        $this->assertType(PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY, $properties);
        $this->assertEquals(2, count($properties));
        $property = $properties[0];
        $this->assertSame($nt, $property->getDeclaringNodeType());
        $this->assertSame('jcr:createdBy',$property->getName());
        $this->assertSame(true,$property->isAutoCreated());
        $this->assertSame(false,$property->isMandatory());
        $this->assertSame(PHPCR_Version_OnParentVersionAction::COPY,$property->getOnParentVersion());
        $this->assertSame(true,$property->isProtected());
        $this->assertSame(array(),$property->getDefaultValues());
        
        //PropertyDefinition
        $this->assertSame(PHPCR_PropertyType::STRING, $property->getRequiredType());
        $this->assertSame(array(), $property->getValueConstraints());
        $this->assertSame(false, $property->isMultiple());
        $this->assertSame(array('jcr.operator.equal.to', 'jcr.operator.not.equal.to', 'jcr.operator.greater.than', 'jcr.operator.greater.than.or.equal.to', 'jcr.operator.less.than', 'jcr.operator.less.than.or.equal.to', 'jcr.operator.like'), $property->getAvailableQueryOperators());
        $this->assertSame(true, $property->isFullTextSearchable());
        $this->assertSame(true, $property->isQueryOrderable());
        
        $nt = $ntm->getNodeType('mix:versionable');
        $properties = $nt->getDeclaredPropertyDefinitions();
        $property = $properties[0];
        $this->assertSame(array('nt:version'), $property->getValueConstraints());
        
        $nt = $ntm->getNodeType('mix:simpleVersionable');
        $properties = $nt->getDeclaredPropertyDefinitions();
        $property = $properties[0];
        $defaultValues = $property->getDefaultValues();
        $this->assertEquals(1, count($defaultValues));
        $this->assertType('jackalope_Value', $defaultValues[0]);
        $this->assertSame('true', $defaultValues[0]->getString());
        $this->assertSame(true, $defaultValues[0]->getBoolean());
    }
    
}
