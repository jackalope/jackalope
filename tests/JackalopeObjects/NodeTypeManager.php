<?php
namespace jackalope\tests\JackalopeObjects;

use \PHPUnit_Framework_Constraint_IsType;

require_once(dirname(__FILE__) . '/../inc/JackalopeObjectsCase.php');

/**
 * @covers: NodeTypeManager
 */
class NodeTypeManager extends \jackalope\JackalopeObjectsCase {
    protected $ntm;

    public function setUp() {
        $this->ntm = $this->getNodeTypeManager();
    }

    /**
     * @covers jackalope\NodeType\NodeTypeManager::getNodeType
     */
    public function testGetNodeType() {
        $nt = $this->ntm->getNodeType('nt:file');
        $this->assertType('jackalope\NodeType\NodeType', $nt);
        $this->assertSame('nt:file', $nt->getName());
        $this->assertFalse($nt->isAbstract());
        $this->assertFalse($nt->isMixin());
        $this->assertFalse($nt->hasOrderableChildNodes());
        $this->assertTrue($nt->isQueryable());
        $this->assertSame('jcr:content', $nt->getPrimaryItemName());
    }
    /**
     * @covers jackalope\NodeType\NodeTypeManager::getDeclaredSubtypes
     * @covers jackalope\NodeType\NodeTypeManager::getSubtypes
     */
    public function testTypeHierarchies() {
        $nt = $this->ntm->getNodeType('nt:file');
        $this->assertSame(array('nt:hierarchyNode'), $nt->getDeclaredSupertypeNames());
        $this->assertSame(array(), $this->ntm->getDeclaredSubtypes('nt:file'));
        $this->assertSame(array(), $this->ntm->getSubtypes('nt:file'));
        $this->assertSame(array('nt:file', 'nt:folder', 'nt:linkedFile', 'rep:Authorizable', 'rep:AuthorizableFolder'), $this->ntm->getDeclaredSubtypes('nt:hierarchyNode'));
        $this->assertSame(array('nt:file', 'nt:folder', 'nt:linkedFile', 'rep:Authorizable', 'rep:Group', 'rep:User', 'rep:AuthorizableFolder'), $this->ntm->getSubtypes('nt:hierarchyNode'));
    }

    /**
     * @covers jackalope\NodeType\NodeTypeManager::hasNodeType
     */
    public function testHasNodeType() {
        $this->assertTrue($this->ntm->hasNodeType('nt:folder'), 'manager claimed to not know about nt:folder');
        $this->assertFalse($this->ntm->hasNodeType('nonode'), 'manager claimed to know about nonode');
    }

    public function testCountTypeClasses() {
        $allNodes = $this->ntm->getAllNodeTypes();
        $this->assertType('Iterator', $allNodes);
        $this->assertEquals(52, count($allNodes));
        $this->assertType('jackalope\NodeType\NodeType', $allNodes->current());
        $primaryNodes = $this->ntm->getPrimaryNodeTypes();
        $this->assertType('Iterator', $primaryNodes);
        $this->assertEquals(36, count($primaryNodes));
        $this->assertType('jackalope\NodeType\NodeType', $primaryNodes->current());
        $mixinNodes = $this->ntm->getMixinNodeTypes();
        $this->assertType('Iterator', $mixinNodes);
        $this->assertEquals(16, count($mixinNodes));
        $this->assertType('jackalope\NodeType\NodeType', $mixinNodes->current());
    }

    /**
     * @covers jackalope\NodeType\NodeTypeManager::createNodeTypeTemplate
     */
    public function testCreateNodeTypeTemplate() {
        $ntm = $this->getNodeTypeManager();

        $nt = $ntm->getNodeType('nt:file');
        $ntt = $ntm->createNodeTypeTemplate($nt);

        $this->assertThat($ntt, $this->isInstanceOf('\jackalope\NodeType\NodeTypeDefinition'));
        $this->assertType('jackalope\NodeType\NodeTypeTemplate', $ntt);
        $this->assertSame('nt:file', $ntt->getName());

        $ntt->setName('nt:file-ext');
        $this->assertSame('nt:file-ext', $ntt->getName());
    }

}

