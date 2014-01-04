<?php

namespace Jackalope;

class ItemTest extends TestCase
{
    /**
     * create the item and mock any of the constructor parameters not specified explicitly
     */
    protected function getItem($factory = null, $path = null, $session = null, $objectManager = null, $new = false)
    {
        if (! $factory) {
            $factory = $this->getMockBuilder('Jackalope\FactoryInterface')->disableOriginalConstructor()->getMock();
        }
        if (! $path) {
            $path = '/';
        }
        if (! $session) {
            $session = $this->getSessionMock();
        }
        if (! $objectManager) {
            $objectManager = $this->getObjectManagerMock();
        }

        return new TestItem($factory, $path, $session, $objectManager, $new);
    }

    /**
     * a mock that will additionally expect getNodeByPath once with $path and return the string 'placeholder'
     */
    protected function getObjectManagerMockWithPath($path)
    {
        $om = $this->getObjectManagerMock();
        $om->expects($this->once())
            ->method('getNodeByPath')
            ->with($this->equalTo($path))
            ->will($this->returnValue('placeholder'))
        ;

        return $om;
    }

    public function testPath()
    {
        $item = $this->getItem();
        $item->setPath('/b');
        $this->assertEquals('/b', $item->getPath());
    }

    public function testName()
    {
        $item = $this->getItem(null, '/path/itemname');
        $this->assertEquals('itemname', $item->getName());
        $item->setPath('/other/name');
        $this->assertEquals('name', $item->getName());
    }

    public function testGetAncestor()
    {
        $om = $this->getObjectManagerMockWithPath('/path');
        $item = $this->getItem(null, '/path/name', null, $om);

        $self = $item->getAncestor(2);
        $this->assertSame($item, $self);

        $ancestor = $item->getAncestor(1);
        $this->assertSame('placeholder', $ancestor);
    }

    public function testGetAncestorRoot()
    {
        $om = $this->getObjectManagerMockWithPath('/');

        $item = $this->getItem(null, '/path/name', null, $om);

        $ancestor = $item->getAncestor(0);
        $this->assertSame('placeholder', $ancestor);
    }

    /**
     * @expectedException \PHPCR\ItemNotFoundException
     */
    public function testGetAncestorTooDeep()
    {
        $item = $this->getItem(null, '/path/name');
        $ancestor = $item->getAncestor(3);
    }

    /**
     * @expectedException \PHPCR\ItemNotFoundException
     */
    public function testGetAncestorTooLow()
    {
        $item = $this->getItem(null, '/path/name');
        $ancestor = $item->getAncestor(-1);
    }

    public function testGetParent()
    {
        $om = $this->getObjectManagerMockWithPath('/path');
        $item = $this->getItem(null, '/path/name', null, $om);
        $parent = $item->getParent();
        $this->assertSame('placeholder', $parent);
    }

    public function testGetDepth()
    {
        $item = $this->getItem(null, '/path/name');
        $this->assertEquals(2, $item->getDepth());
        $item = $this->getItem(null, '/');
        $this->assertEquals(0, $item->getDepth());
    }

    public function testGetSession()
    {
        $session = $this->getSessionMock();
        $item = $this->getItem(null, null, $session);
        $this->assertEquals($session, $item->getSession());
    }

    public function testIsSame()
    {
        $this->markTestSkipped('TODO: do some mean stuff');
    }

    public function testAccept()
    {
        $item = $this->getItem();
        $visitor = $this->getMock('\PHPCR\ItemVisitorInterface');
        $visitor->expects($this->once())
                ->method('visit');
        $item->accept($visitor);
    }

    public function testRemove()
    {
        $om = $this->getObjectManagerMock();
        $om->expects($this->once())
            ->method('removeItem')
            ->with($this->equalTo('/path'))
        ;

        $item = $this->getItem(null, '/path', null, $om);
        $item->remove();
        $this->assertTrue($item->isDeleted());
    }

    /**
     * @expectedException \PHPCR\RepositoryException
     */
    public function testRemoveRoot()
    {
        $item = $this->getItem(null, '/');
        $item->remove();
    }

    // TODO: test the whole state model processes (probably in separate test case)
}

class TestItem extends Item
{
    public function __construct(FactoryInterface $factory, $path, Session $session, ObjectManager $objectManager, $new = false)
    {
        parent::__construct($factory,$path,$session,$objectManager,$new);
    }

    public function refresh($keep)
    {
        // tested in extending classes
    }
}
