<?php

namespace Jackalope;

class NodeTest extends TestCase
{
    protected $JSON = '{":jcr:primaryType":"Name","jcr:primaryType":"rep:root","jcr:system":{},"tests_level1_access_base":{}}';

    protected function createNode()
    {
        $factory = new Factory();
        $session = $this->getSessionMock();
        $objectManager = $this->getObjectManagerMock();
        $objectManager->expects($this->any())
            ->method('getNodesByPath')
            ->will($this->returnValue(new \ArrayIterator([
                '/jcr:root/tests_level1_access_base' => new Node($factory, json_decode($this->JSON), '/jcr:root/tests_level1_access_base', $session, $objectManager),
                '/jcr:root/jcr:system' => new Node($factory, json_decode($this->JSON), '/jcr:root/jcr:system', $session, $objectManager),
            ])))
        ;
        $node = new Node($factory, json_decode($this->JSON), '/jcr:root', $session, $objectManager);

        return $node;
    }

    public function testConstructor()
    {
        $node = $this->createNode();
        $this->assertInstanceOf(Session::class, $node->getSession());
        $this->assertInstanceOf(Node::class, $node);
        $children = $node->getNodes();
        $this->assertInstanceOf(\Iterator::class, $children);
        $this->assertCount(2, $children);
    }

    public function testNodeType()
    {
        $node = $this->createNode();
        $this->assertTrue($node->isNodeType('rep:root'), "Should return true on is 'rep:root' node type.");
        // TODO: Activate this…
        // $this->assertTrue($node->getPrimaryNodeType()->isNodeType('rep:root'));
    }

    public function testFilterNames()
    {
        $filter = 'test';
        $names = ['test', 'toast'];
        $filtered = NodeMock::filterNames($filter, $names);
        $this->assertIsArray($filtered);
        $this->assertCount(1, $filtered);
        $this->assertSame('test', $filtered[0]);

        $filter = 't*t';
        $filtered = NodeMock::filterNames($filter, $names);
        $this->assertIsArray($filtered);
        $this->assertCount(2, $filtered);
        $this->assertSame('test', $filtered[0]);
        $this->assertSame('toast', $filtered[1]);

        $filter = 'te.t';
        $filtered = NodeMock::filterNames($filter, $names);
        $this->assertIsArray($filtered);
        $this->assertCount(0, $filtered);

        $filter = 'test|toast';
        $filtered = NodeMock::filterNames($filter, $names);
        $this->assertIsArray($filtered);
        $this->assertCount(2, $filtered);
        $this->assertSame('test', $filtered[0]);
        $this->assertSame('toast', $filtered[1]);

        $filter = 'test|toast ';
        $filtered = NodeMock::filterNames($filter, $names);
        $this->assertIsArray($filtered);
        $this->assertCount(2, $filtered);
        $this->assertSame('test', $filtered[0]);
        $this->assertSame('toast', $filtered[1]);

        $filter = ['test ', 'toa*'];
        $filtered = NodeMock::filterNames($filter, $names);
        $this->assertIsArray($filtered);
        $this->assertCount(2, $filtered);
        $this->assertSame('test', $filtered[0]);
        $this->assertSame('toast', $filtered[1]);

        $filter = null;
        $filtered = NodeMock::filterNames($filter, $names);
        $this->assertIsArray($filtered);
        $this->assertCount(2, $filtered);
        $this->assertSame('test', $filtered[0]);
        $this->assertSame('toast', $filtered[1]);

        $filter = '*';
        $filtered = NodeMock::filterNames($filter, $names);
        $this->assertIsArray($filtered);
        $this->assertCount(2, $filtered);
        $this->assertSame('test', $filtered[0]);
        $this->assertSame('toast', $filtered[1]);

        $filter = ['*'];
        $filtered = NodeMock::filterNames($filter, $names);
        $this->assertIsArray($filtered);
        $this->assertCount(2, $filtered);
        $this->assertSame('test', $filtered[0]);
        $this->assertSame('toast', $filtered[1]);
    }
}

class NodeMock extends Node
{
    public static function filterNames($filter, $names)
    {
        return parent::filterNames($filter, $names);
    }
}
