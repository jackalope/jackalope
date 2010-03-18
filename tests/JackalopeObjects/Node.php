<?php
require_once(dirname(__FILE__) . '/../inc/baseCase.php');

class jackalope_tests_Node extends jackalope_baseCase {
    private $JSON = '{":jcr:primaryType":"Name","jcr:primaryType":"rep:root","jcr:system":{},"tests_level1_access_base":{}}';
    public function testConstructor() {
        $session = $this->getMock('jackalope_Session', array(), array(), '', false);
        $objectManager = $this->getMock('jackalope_ObjectManager', array(), array(), '', false);
        $node = new jackalope_Node(json_decode($this->JSON), '/jcr:node', $session, $objectManager);
        $this->assertSame($session, $node->getSession());
        $this->assertType('jackalope_Node', $node);
        $this->assertTrue($node->getPrimaryNodeType()->isNodeType('rep:root'));
        $children = $this->getNodes();
        $this->assertType('jackalope_NodeIterator', $children);
        $this->assertEqual(2, $children->size());
    }
    public function testFilterNames() {
        $filter = 'test';
        $names = array('test', 'toast');
        $filtered = jackalope_tests_Node_PublicFilter::filterNames($filter, $names);
        $this->assertType('array', $filtered);
        $this->assertEquals(1, count($filtered));
        $this->assertEquals('test', $filtered[0]);

        $filter = 't*t';
        $filtered = jackalope_tests_Node_PublicFilter::filterNames($filter, $names);
        $this->assertType('array', $filtered);
        $this->assertEquals(2, count($filtered));
        $this->assertEquals('test', $filtered[0]);
        $this->assertEquals('toast', $filtered[1]);

        $filter = 'te.t';
        $filtered = jackalope_tests_Node_PublicFilter::filterNames($filter, $names);
        $this->assertType('array', $filtered);
        $this->assertEquals(0, count($filtered));

        $filter = 'test|toast';
        $filtered = jackalope_tests_Node_PublicFilter::filterNames($filter, $names);
        $this->assertType('array', $filtered);
        $this->assertEquals(2, count($filtered));
        $this->assertEquals('test', $filtered[0]);
        $this->assertEquals('toast', $filtered[1]);

        $filter = 'test|toast ';
        $filtered = jackalope_tests_Node_PublicFilter::filterNames($filter, $names);
        $this->assertType('array', $filtered);
        $this->assertEquals(2, count($filtered));
        $this->assertEquals('test', $filtered[0]);
        $this->assertEquals('toast', $filtered[1]);

        $filter = array('test ', 'toa*');
        $filtered = jackalope_tests_Node_PublicFilter::filterNames($filter, $names);
        $this->assertType('array', $filtered);
        $this->assertEquals(2, count($filtered));
        $this->assertEquals('test', $filtered[0]);
        $this->assertEquals('toast', $filtered[1]);

        $filter = null;
        $filtered = jackalope_tests_Node_PublicFilter::filterNames($filter, $names);
        $this->assertType('array', $filtered);
        $this->assertEquals(2, count($filtered));
        $this->assertEquals('test', $filtered[0]);
        $this->assertEquals('toast', $filtered[1]);

        $filter = '*';
        $filtered = jackalope_tests_Node_PublicFilter::filterNames($filter, $names);
        $this->assertType('array', $filtered);
        $this->assertEquals(2, count($filtered));
        $this->assertEquals('test', $filtered[0]);
        $this->assertEquals('toast', $filtered[1]);

        $filter = array('*');
        $filtered = jackalope_tests_Node_PublicFilter::filterNames($filter, $names);
        $this->assertType('array', $filtered);
        $this->assertEquals(2, count($filtered));
        $this->assertEquals('test', $filtered[0]);
        $this->assertEquals('toast', $filtered[1]);
    }
}
class jackalope_tests_Node_PublicFilter extends jackalope_Node {
    public static function filterNames($filter,$names) {
        return parent::filterNames($filter,$names);
    }
}