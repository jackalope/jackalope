<?php

namespace Jackalope\Functional\Transport;

use Jackalope\TestCase;
use Jackalope\Transport\TransportInterface;

/**
 * Extend this test case in your jackalope transport and provide the transport
 * instance to be tested.
 *
 * The fixtures must contain the following tree:
 *
 * * node-a
 * * * child-a
 * * * child-b
 * * node-b
 * * * child-a
 * * * child-b
 *
 * each child has a property "prop" with the corresponding a and b value in it:
 * /node-a/child-a get "prop" => "aa".
 */
abstract class PrefetchTestCase extends TestCase
{
    /**
     * @return TransportInterface A real transport instance that is already logged in.
     */
    abstract protected function getTransport();

    public function testGetNode()
    {
        $transport = $this->getTransport();
        $transport->setFetchDepth(1);

        $raw = $transport->getNode('/node-a');

        $this->assertNode($raw, 'a');
    }

    public function testGetNodes()
    {
        $transport = $this->getTransport();
        $transport->setFetchDepth(1);

        $list = $transport->getNodes(array('/node-a', '/node-b'));

        list($key, $raw) = each($list);
        $this->assertEquals('/node-a', $key);
        $this->assertNode($raw, 'a');

        list($key, $raw) = each($list);
        $this->assertEquals('/node-b', $key);
        $this->assertNode($raw, 'b');
    }

    protected function assertNode($raw, $parent)
    {
        $this->assertInstanceOf('\stdClass', $raw);
        $name = "child-a";
        $this->assertTrue(isset($raw->$name), "The raw data is missing child $name");
        $this->assertInstanceOf('\stdClass', $raw->$name);
        $this->assertTrue(isset($raw->$name->prop), "The child $name is missing property 'prop'");
        $this->assertEquals($parent . 'a', $raw->$name->prop);

        $name = 'child-b';
        $this->assertTrue(isset($raw->$name));
        $this->assertInstanceOf('\stdClass', $raw->$name);
        $this->assertTrue(isset($raw->$name->prop), "The child $name is missing property 'prop'");
        $this->assertEquals($parent . 'b', $raw->$name->prop);
    }
}
