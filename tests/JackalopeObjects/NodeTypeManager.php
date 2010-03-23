<?php
require_once(dirname(__FILE__) . '/../inc/JackalopeObjectsCase.php');

class jackalope_tests_NodeTypeManager extends jackalope_JackalopeObjectsCase {
    
    public function testGetNodeType() {
        $ntm = $this->getNodeTypeManager();
        $nt = $ntm->getNodeType('nt:folder');
        $this->assertType('jackalope_NodeType_NodeType', $nt);
    }
    
}
