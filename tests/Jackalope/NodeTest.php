<?php

namespace Jackalope;

class NodeTest extends TestCase
{
    protected $JSON = '{":jcr:primaryType":"Name","jcr:primaryType":"rep:root","jcr:system":{},"tests_level1_access_base":{}}';

    protected function createNode(): Node
    {
        $factory = new Factory();
        $session = $this->getSessionMock();
        $objectManager = $this->createMock(ObjectManager::class);
        $objectManager
            ->method('getNodesByPath')
            ->willReturn(new \ArrayIterator([
                '/jcr:root/tests_level1_access_base' => new Node($factory, json_decode($this->JSON), '/jcr:root/tests_level1_access_base', $session, $objectManager),
                '/jcr:root/jcr:system' => new Node($factory, json_decode($this->JSON), '/jcr:root/jcr:system', $session, $objectManager),
            ]))
        ;

        return new Node($factory, json_decode($this->JSON), '/jcr:root', $session, $objectManager);
    }

    public function testConstructor(): void
    {
        $node = $this->createNode();
        $this->assertInstanceOf(Session::class, $node->getSession());
        $children = $node->getNodes();
        $this->assertInstanceOf(\Iterator::class, $children);
        $this->assertCount(2, $children);
    }

    public function testNodeType(): void
    {
        $node = $this->createNode();
        $this->assertTrue($node->isNodeType('rep:root'), "Should return true on is 'rep:root' node type.");
        // TODO: Activate this…
        // $this->assertTrue($node->getPrimaryNodeType()->isNodeType('rep:root'));
    }

    public function testFilterNames(): void
    {
        $nodeReflection = new \ReflectionClass(Node::class);
        $filterNames = $nodeReflection->getMethod('filterNames');
        $filterNames->setAccessible(true);

        $filter = 'test';
        $names = ['test', 'toast'];
        $filtered = $filterNames->invoke(null, $filter, $names);
        $this->assertIsArray($filtered);
        $this->assertCount(1, $filtered);
        $this->assertSame('test', $filtered[0]);

        $filter = 't*t';
        $filtered = $filterNames->invoke(null, $filter, $names);
        $this->assertIsArray($filtered);
        $this->assertCount(2, $filtered);
        $this->assertSame('test', $filtered[0]);
        $this->assertSame('toast', $filtered[1]);

        $filter = 'te.t';
        $filtered = $filterNames->invoke(null, $filter, $names);
        $this->assertIsArray($filtered);
        $this->assertCount(0, $filtered);

        $filter = 'test|toast';
        $filtered = $filterNames->invoke(null, $filter, $names);
        $this->assertIsArray($filtered);
        $this->assertCount(2, $filtered);
        $this->assertSame('test', $filtered[0]);
        $this->assertSame('toast', $filtered[1]);

        $filter = 'test|toast ';
        $filtered = $filterNames->invoke(null, $filter, $names);
        $this->assertIsArray($filtered);
        $this->assertCount(2, $filtered);
        $this->assertSame('test', $filtered[0]);
        $this->assertSame('toast', $filtered[1]);

        $filter = ['test ', 'toa*'];
        $filtered = $filterNames->invoke(null, $filter, $names);
        $this->assertIsArray($filtered);
        $this->assertCount(2, $filtered);
        $this->assertSame('test', $filtered[0]);
        $this->assertSame('toast', $filtered[1]);

        $filter = null;
        $filtered = $filterNames->invoke(null, $filter, $names);
        $this->assertIsArray($filtered);
        $this->assertCount(2, $filtered);
        $this->assertSame('test', $filtered[0]);
        $this->assertSame('toast', $filtered[1]);

        $filter = '*';
        $filtered = $filterNames->invoke(null, $filter, $names);
        $this->assertIsArray($filtered);
        $this->assertCount(2, $filtered);
        $this->assertSame('test', $filtered[0]);
        $this->assertSame('toast', $filtered[1]);

        $filter = ['*'];
        $filtered = $filterNames->invoke(null, $filter, $names);
        $this->assertIsArray($filtered);
        $this->assertCount(2, $filtered);
        $this->assertSame('test', $filtered[0]);
        $this->assertSame('toast', $filtered[1]);
    }
}
