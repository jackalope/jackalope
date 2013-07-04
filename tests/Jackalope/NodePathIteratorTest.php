<?php

namespace Jackalope;

use PHPCR\NodeInterface;
use Jackalope\Node;
use Jackalope\Transport\NodeTypeFilterInterface;
use Jackalope\Transport\TransportInterface;
use Jackalope\NodeIterator;

class NodeIteratorTest extends \PHPUnit_Framework_Testcase
{
    public function setUp()
    {
        $this->objectManager = $this->getMockBuilder('Jackalope\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function provideIterator()
    {
        return array(
            array(array('/foo1'), 'Node', 'nt:foo', 2),
            array(array('/foo1', '/foo2', '/foo3', '/foo4'), 'Node', 'nt:foo', 2),
            array(array('/foo1', '/foo2', '/foo3', '/foo4', '/foo5'), 'Node', 'nt:foo', 2),
            array(array('/foo1', '/foo2', '/foo3', '/foo4'), 'Node', 'nt:foo', 10),
        );
    }

    /**
     * @dataProvider provideIterator
     */
    public function testIterator($paths, $class, $filter, $batchSize)
    {
        $me = $this;
        $nbBatches = (integer) ceil(count($paths) / $batchSize);
        $this->objectManager->expects($this->exactly($nbBatches))
            ->method('getNodesByPathAsArray')
            ->will($this->returnCallback(function (
                $cPaths, $cClass, $cFilter
            ) use (
                $me, $class, $filter, $batchSize
            ) {
                $this->assertLessThanOrEqual($batchSize, count($cPaths));
                $nodes = array();
                $this->assertEquals($class, $cClass);
                $this->assertEquals($filter, $cFilter);
                foreach ($cPaths as $cPath) {
                    $nodes[$cPath] = $this->getMockBuilder('Jackalope\Node')
                        ->disableOriginalConstructor()
                        ->getMock();
                }
                return $nodes;
            }));

        $nodes = new NodePathIterator($this->objectManager, $paths, $class, $filter, $batchSize);

        foreach ($nodes as $node) {
            $this->assertInstanceOf('Jackalope\Node', $node);
        }
    }

    public function testArrayAccess()
    {
        $this->objectManager->expects($this->once())
            ->method('getNodesByPathAsArray')
            ->will($this->returnCallback(function ($paths) {
                $nodes = array();
                foreach ($paths as $i => $path) {
                    $node = $this->getMockBuilder('Jackalope\Node')
                        ->disableOriginalConstructor()
                        ->getMock();
                    $node->expects($this->once())
                        ->method('getIdentifier')
                        ->will($this->returnValue($path));
                    $nodes[$path] = $node;
                }

                return $nodes;
            }));

        $nodes = new NodePathIterator($this->objectManager, array('/foo1'));

        foreach ($nodes as $path => $node) {
            $this->assertInstanceOf('Jackalope\Node', $node);
            $this->assertEquals($path, $node->getIdentifier());
        }

        $this->assertTrue(isset($nodes['/foo1']));
        $this->assertFalse(isset($nodes['invalid']));
    }
}
