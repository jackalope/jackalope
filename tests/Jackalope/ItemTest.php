<?php

namespace Jackalope;

use PHPCR\ItemNotFoundException;
use PHPCR\ItemVisitorInterface;
use PHPCR\NodeInterface;
use PHPCR\RepositoryException;
use PHPUnit\Framework\MockObject\MockObject;

class ItemTest extends TestCase
{
    /**
     * create the item and mock any of the constructor parameters not specified explicitly.
     */
    protected function getItem($factory = null, $path = null, $session = null, $objectManager = null, $new = false): TestItem
    {
        if (!$factory) {
            $factory = new Factory();
        }
        if (!$path) {
            $path = '/';
        }
        if (!$session) {
            $session = $this->createMock(Session::class);
        }
        if (!$objectManager) {
            $objectManager = $this->createMock(ObjectManager::class);
        }

        return new TestItem($factory, $path, $session, $objectManager, $new);
    }

    /**
     * a mock that will additionally expect getNodeByPath once with $path and return the string 'placeholder'.
     *
     * @return ObjectManager&MockObject
     */
    protected function getObjectManagerMockWithPath($path, NodeInterface $node)
    {
        $om = $this->createMock(ObjectManager::class);
        $om->expects($this->once())
            ->method('getNodeByPath')
            ->with($this->equalTo($path))
            ->willReturn($node)
        ;

        return $om;
    }

    public function testPath(): void
    {
        $item = $this->getItem();
        $item->setPath('/b');
        $this->assertEquals('/b', $item->getPath());
    }

    public function testName(): void
    {
        $item = $this->getItem(null, '/path/itemname');
        $this->assertEquals('itemname', $item->getName());
        $item->setPath('/other/name');
        $this->assertEquals('name', $item->getName());
    }

    public function testGetAncestor(): void
    {
        $node = $this->createMock(NodeInterface::class);
        $om = $this->getObjectManagerMockWithPath('/path', $node);
        $item = $this->getItem(null, '/path/name', null, $om);

        $self = $item->getAncestor(2);
        $this->assertSame($item, $self);

        $ancestor = $item->getAncestor(1);
        $this->assertSame($node, $ancestor);
    }

    public function testGetAncestorRoot(): void
    {
        $node = $this->createMock(NodeInterface::class);
        $om = $this->getObjectManagerMockWithPath('/', $node);

        $item = $this->getItem(null, '/path/name', null, $om);

        $ancestor = $item->getAncestor(0);
        $this->assertSame($node, $ancestor);
    }

    public function testGetAncestorTooDeep(): void
    {
        $this->expectException(ItemNotFoundException::class);

        $item = $this->getItem(null, '/path/name');
        $item->getAncestor(3);
    }

    public function testGetAncestorTooLow(): void
    {
        $this->expectException(ItemNotFoundException::class);

        $item = $this->getItem(null, '/path/name');
        $item->getAncestor(-1);
    }

    public function testGetParent(): void
    {
        $node = $this->createMock(NodeInterface::class);
        $om = $this->getObjectManagerMockWithPath('/path', $node);
        $item = $this->getItem(null, '/path/name', null, $om);
        $parent = $item->getParent();
        $this->assertSame($node, $parent);
    }

    public function testGetDepth(): void
    {
        $item = $this->getItem(null, '/path/name');
        $this->assertEquals(2, $item->getDepth());
        $item = $this->getItem(null, '/');
        $this->assertEquals(0, $item->getDepth());
    }

    public function testGetSession(): void
    {
        $session = $this->getSessionMock();
        $item = $this->getItem(null, null, $session);
        $this->assertEquals($session, $item->getSession());
    }

    public function testIsSame(): void
    {
        $this->markTestSkipped('TODO: do some mean stuff');
    }

    public function testAccept(): void
    {
        $item = $this->getItem();
        $visitor = $this->createMock(ItemVisitorInterface::class);
        $visitor->expects($this->once())
                ->method('visit');
        $item->accept($visitor);
    }

    public function testRemove(): void
    {
        $om = $this->createMock(ObjectManager::class);
        $om->expects($this->once())
            ->method('removeItem')
            ->with($this->equalTo('/path'))
        ;

        $item = $this->getItem(null, '/path', null, $om);
        $item->remove();
        $this->assertTrue($item->isDeleted());
    }

    public function testRemoveRoot(): void
    {
        $this->expectException(RepositoryException::class);

        $item = $this->getItem(null, '/');
        $item->remove();
    }

    // TODO: test the whole state model processes (probably in separate test case)
}

class TestItem extends Item
{
    public function __construct(FactoryInterface $factory, $path, Session $session, ObjectManager $objectManager, $new = false)
    {
        parent::__construct($factory, $path, $session, $objectManager, $new);
    }

    public function refresh(bool $keepChanges, bool $internal = false): void
    {
        // tested in extending classes
    }
}
