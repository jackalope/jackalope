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
        // $this->assertSame('nt:folder', $nt->getDeclaredPropertyDefinitions());
        // $this->assertSame('nt:folder', $nt->getDeclaredChildNodeDefinitions());
        
        $nt = $ntm->getNodeType('mix:created');
        $this->assertType('jackalope_NodeType_NodeType', $nt);
        $this->assertSame('mix:created', $nt->getName());
        $this->assertSame(array(), $nt->getDeclaredSupertypeNames());
        $this->assertSame(false, $nt->isAbstract());
        $this->assertSame(true, $nt->isMixin());
        $this->assertSame(false, $nt->hasOrderableChildNodes());
        $this->assertSame(true, $nt->isQueryable());
        $this->assertSame(null, $nt->getPrimaryItemName());
    }
    
}
