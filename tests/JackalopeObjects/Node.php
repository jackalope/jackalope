<?php
namespace jackalope\tests\JackalopeObjects;

require_once(dirname(__FILE__) . '/../inc/baseCase.php');

class Node extends \jackalope\baseCase {
    private $JSON = '{":jcr:primaryType":"Name","jcr:primaryType":"rep:root","jcr:system":{},"tests_level1_access_base":{}}';
    public function testConstructor() {
        $session = $this->getMock('\jackalope\Session', array(), array(), '', false);
        $objectManager = $this->getMock('\jackalope\ObjectManager', array(), array(), '', false);
        $node = new \jackalope\Node(json_decode($this->JSON), '/jcr:node', $session, $objectManager);
        $this->assertSame($session, $node->getSession());
        $this->assertType('jackalope\Node', $node);
        //TODO: Activate this…
        // $this->assertTrue($node->getPrimaryNodeType()->isNodeType('rep:root'));
        $children = $node->getNodes();
        $this->assertType('Iterator', $children);
        $this->assertEquals(2, count($children));
    }
    public function testFilterNames() {
        $filter = 'test';
        $names = array('test', 'toast');
        $filtered = PublicFilter::filterNames($filter, $names);
        $this->assertType('array', $filtered);
        $this->assertEquals(1, count($filtered));
        $this->assertEquals('test', $filtered[0]);

        $filter = 't*t';
        $filtered = PublicFilter::filterNames($filter, $names);
        $this->assertType('array', $filtered);
        $this->assertEquals(2, count($filtered));
        $this->assertEquals('test', $filtered[0]);
        $this->assertEquals('toast', $filtered[1]);

        $filter = 'te.t';
        $filtered = PublicFilter::filterNames($filter, $names);
        $this->assertType('array', $filtered);
        $this->assertEquals(0, count($filtered));

        $filter = 'test|toast';
        $filtered = PublicFilter::filterNames($filter, $names);
        $this->assertType('array', $filtered);
        $this->assertEquals(2, count($filtered));
        $this->assertEquals('test', $filtered[0]);
        $this->assertEquals('toast', $filtered[1]);

        $filter = 'test|toast ';
        $filtered = PublicFilter::filterNames($filter, $names);
        $this->assertType('array', $filtered);
        $this->assertEquals(2, count($filtered));
        $this->assertEquals('test', $filtered[0]);
        $this->assertEquals('toast', $filtered[1]);

        $filter = array('test ', 'toa*');
        $filtered = PublicFilter::filterNames($filter, $names);
        $this->assertType('array', $filtered);
        $this->assertEquals(2, count($filtered));
        $this->assertEquals('test', $filtered[0]);
        $this->assertEquals('toast', $filtered[1]);

        $filter = null;
        $filtered = PublicFilter::filterNames($filter, $names);
        $this->assertType('array', $filtered);
        $this->assertEquals(2, count($filtered));
        $this->assertEquals('test', $filtered[0]);
        $this->assertEquals('toast', $filtered[1]);

        $filter = '*';
        $filtered = PublicFilter::filterNames($filter, $names);
        $this->assertType('array', $filtered);
        $this->assertEquals(2, count($filtered));
        $this->assertEquals('test', $filtered[0]);
        $this->assertEquals('toast', $filtered[1]);

        $filter = array('*');
        $filtered = PublicFilter::filterNames($filter, $names);
        $this->assertType('array', $filtered);
        $this->assertEquals(2, count($filtered));
        $this->assertEquals('test', $filtered[0]);
        $this->assertEquals('toast', $filtered[1]);
    }
}
class PublicFilter extends \jackalope\Node {
    public static function filterNames($filter,$names) {
        return parent::filterNames($filter,$names);
    }
}
