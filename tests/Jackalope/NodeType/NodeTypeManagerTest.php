<?php

namespace Jackalope\NodeType;

use Jackalope\TestCase;

/**
 * TODO: this is more of a jackrabbit specific functional test than a real unit test. Mock more.
 */
final class NodeTypeManagerTest extends TestCase
{
    private NodeTypeManager $ntm;

    public function setUp(): void
    {
        $this->ntm = $this->getNodeTypeManager();
    }

    /**
     * @covers \Jackalope\NodeType\NodeTypeManager::getNodeType
     */
    public function testGetNodeType(): void
    {
        $nt = $this->ntm->getNodeType('nt:file');
        $this->assertInstanceOf(NodeType::class, $nt);
        $this->assertSame('nt:file', $nt->getName());
        $this->assertFalse($nt->isAbstract());
        $this->assertFalse($nt->isMixin());
        $this->assertFalse($nt->hasOrderableChildNodes());
        $this->assertTrue($nt->isQueryable());
        $this->assertSame('jcr:content', $nt->getPrimaryItemName());
    }

    /**
     * @covers \Jackalope\NodeType\NodeTypeManager::getDeclaredSubtypes
     * @covers \Jackalope\NodeType\NodeTypeManager::getSubtypes
     */
    public function testTypeHierarchies(): void
    {
        $nt = $this->ntm->getNodeType('nt:file');
        $this->assertSame(['nt:hierarchyNode'], $nt->getDeclaredSupertypeNames());
        $this->assertEquals([], $this->ntm->getDeclaredSubtypes('nt:file'));
        $this->assertEquals([], $this->ntm->getSubtypes('nt:file'));
        $this->assertSame(['nt:file', 'nt:folder', 'nt:linkedFile', 'rep:Authorizable', 'rep:AuthorizableFolder'], array_keys($this->ntm->getDeclaredSubtypes('nt:hierarchyNode')));
        $this->assertSame(['nt:file', 'nt:folder', 'nt:linkedFile', 'rep:Authorizable', 'rep:Group', 'rep:User', 'rep:AuthorizableFolder'], array_keys($this->ntm->getSubtypes('nt:hierarchyNode')));
    }

    /**
     * @covers \Jackalope\NodeType\NodeTypeManager::hasNodeType
     */
    public function testHasNodeType(): void
    {
        $this->assertTrue($this->ntm->hasNodeType('nt:folder'), 'manager claimed to not know about nt:folder');
        $this->assertFalse($this->ntm->hasNodeType('nonode'), 'manager claimed to know about nonode');
    }

    public function testCountTypeClasses(): void
    {
        $allNodes = $this->ntm->getAllNodeTypes();
        $this->assertInstanceOf('Iterator', $allNodes);
        $this->assertCount(52, $allNodes);
        $this->assertInstanceOf(NodeType::class, $allNodes->current());
        $primaryNodes = $this->ntm->getPrimaryNodeTypes();
        $this->assertInstanceOf('Iterator', $primaryNodes);
        $this->assertCount(36, $primaryNodes);
        $this->assertInstanceOf(NodeType::class, $primaryNodes->current());
        $mixinNodes = $this->ntm->getMixinNodeTypes();
        $this->assertInstanceOf('Iterator', $mixinNodes);
        $this->assertCount(16, $mixinNodes);
        $this->assertInstanceOf(NodeType::class, $mixinNodes->current());
    }
}
