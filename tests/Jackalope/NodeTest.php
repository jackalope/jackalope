<?php

namespace Jackalope;

class NodeTest extends TestCase
{
    protected $JSON = '{":jcr:primaryType":"Name","jcr:primaryType":"rep:root","jcr:system":{},"tests_level1_access_base":{}}';

    protected function createNode()
    {
        $factory = new Factory;
        $session = $this->getSessionMock();
        $objectManager = $this->getMock('Jackalope\ObjectManager', array(), array($factory), '', false);
        $objectManager->expects($this->any())
            ->method('getNodesByPath')
            ->will($this->returnValue(new \ArrayIterator(array(
                "/jcr:root/tests_level1_access_base" => new Node($factory, json_decode($this->JSON), '/jcr:root/tests_level1_access_base', $session, $objectManager),
                "/jcr:root/jcr:system" => new Node($factory, json_decode($this->JSON), '/jcr:root/jcr:system', $session, $objectManager),
            ))))
        ;
        $node = new Node($factory, json_decode($this->JSON), '/jcr:root', $session, $objectManager);
        return $node;
    }

    public function testConstructor()
    {
        $node = $this->createNode();
        $this->assertInstanceOf('Jackalope\Session', $node->getSession());
        $this->assertInstanceOf('Jackalope\Node', $node);
        $children = $node->getNodes();
        $this->assertInstanceOf('Iterator', $children);
        $this->assertSame(2, count($children));
    }

    public function testNodeType()
    {
        $node = $this->createNode();
        $this->assertTrue($node->isNodeType('rep:root'), "Should return true on is 'rep:root' node type.");
        //TODO: Activate this…
        // $this->assertTrue($node->getPrimaryNodeType()->isNodeType('rep:root'));
    }

    public function testFilterNames()
    {
        $filter = 'test';
        $names = array('test', 'toast');
        $filtered = NodeMock::filterNames($filter, $names);
        $this->assertInternalType('array', $filtered);
        $this->assertSame(1, count($filtered));
        $this->assertSame('test', $filtered[0]);

        $filter = 't*t';
        $filtered = NodeMock::filterNames($filter, $names);
        $this->assertInternalType('array', $filtered);
        $this->assertSame(2, count($filtered));
        $this->assertSame('test', $filtered[0]);
        $this->assertSame('toast', $filtered[1]);

        $filter = 'te.t';
        $filtered = NodeMock::filterNames($filter, $names);
        $this->assertInternalType('array', $filtered);
        $this->assertSame(0, count($filtered));

        $filter = 'test|toast';
        $filtered = NodeMock::filterNames($filter, $names);
        $this->assertInternalType('array', $filtered);
        $this->assertSame(2, count($filtered));
        $this->assertSame('test', $filtered[0]);
        $this->assertSame('toast', $filtered[1]);

        $filter = 'test|toast ';
        $filtered = NodeMock::filterNames($filter, $names);
        $this->assertInternalType('array', $filtered);
        $this->assertSame(2, count($filtered));
        $this->assertSame('test', $filtered[0]);
        $this->assertSame('toast', $filtered[1]);

        $filter = array('test ', 'toa*');
        $filtered = NodeMock::filterNames($filter, $names);
        $this->assertInternalType('array', $filtered);
        $this->assertSame(2, count($filtered));
        $this->assertSame('test', $filtered[0]);
        $this->assertSame('toast', $filtered[1]);

        $filter = null;
        $filtered = NodeMock::filterNames($filter, $names);
        $this->assertInternalType('array', $filtered);
        $this->assertSame(2, count($filtered));
        $this->assertSame('test', $filtered[0]);
        $this->assertSame('toast', $filtered[1]);

        $filter = '*';
        $filtered = NodeMock::filterNames($filter, $names);
        $this->assertInternalType('array', $filtered);
        $this->assertSame(2, count($filtered));
        $this->assertSame('test', $filtered[0]);
        $this->assertSame('toast', $filtered[1]);

        $filter = array('*');
        $filtered = NodeMock::filterNames($filter, $names);
        $this->assertInternalType('array', $filtered);
        $this->assertSame(2, count($filtered));
        $this->assertSame('test', $filtered[0]);
        $this->assertSame('toast', $filtered[1]);
    }

    /**
     * @group benchmark
     */
    public function testBenchmarkOrderBeforeArray()
    {
        for ($i = 0; $i < 1000000; $i++) {
            $nodes[] = 'test' . $i;
        }

        $args = array('test250', 'test750', $nodes);

        $node = $this->getMock('\\Jackalope\\Node', array(), array(), '', false);
        $method = new \ReflectionMethod($node, 'orderBeforeArray');
        $method->setAccessible(true);

        $start = microtime(true);

        $method->invokeArgs($node, $args);

        $this->assertLessThan(1.0, microtime(true) - $start);
    }

}

class NodeMock extends Node
{
    public static function filterNames($filter,$names)
    {
        return parent::filterNames($filter,$names);
    }
}
