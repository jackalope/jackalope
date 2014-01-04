<?php

namespace Jackalope;

class NodePathIteratorTest extends TestCase
{
    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $objectManager;

    public function setUp()
    {
        $this->objectManager = $this->getObjectManagerMock();
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
                $me->assertLessThanOrEqual($batchSize, count($cPaths));
                $nodes = array();
                $me->assertEquals($class, $cClass);
                $me->assertEquals($filter, $cFilter);
                foreach ($cPaths as $cPath) {
                    $nodes[$cPath] = $me->getNodeMock();
                }

                return $nodes;
            }));

        $nodes = new NodePathIterator($this->objectManager, $paths, $class, $filter, $batchSize);

        foreach ($nodes as $node) {
            $this->assertInstanceOf('Jackalope\Node', $node);
        }
    }

    public function provideArrayAccess()
    {
        return array(
            // 1st target, batch size 2, 1 fetch
            array(
                array('p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7', 'p8'),
                2,
                array('nb_fetches' => 1, 'target' => 'p1'),
            ),

            // 3rd target, batch size 2, 2 fetches
            array(
                array('p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7', 'p8'),
                2,
                array('nb_fetches' => 2, 'target' => 'p3'),
            ),

            // 3rd target, batch size 1, 3 fetches
            array(
                array('p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7', 'p8'),
                1,
                array('nb_fetches' => 3, 'target' => 'p3'),
            ),

            // test 0 paths
            array(
                array(),
                2,
                array('nb_fetches' => 0),
            ),

            // test partial iteration
            array(
                array('p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7', 'p8'),
                2,
                array('nb_fetches' => 2, 'target' => 'p4', 'iterate_result' => 3)
            ),
            array(
                array('p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7', 'p8'),
                2,
                array('nb_fetches' => 4, 'target' => 'p4', 'iterate_result' => 8)
            ),

            // multiple targets
            array(
                array('p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7', 'p8'),
                2,
                array('nb_fetches' => 3, 'target' => array('p1', 'p2', 'p3', 'p4', 'p5'))
            ),
            array(
                array('p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7', 'p8'),
                2,
                array('nb_fetches' => 4, 'target' => array('p8', 'p1'))
            ),
            array(
                array('p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7', 'p8'),
                100,
                array('nb_fetches' => 1, 'target' => array('p8', 'p1'))
            ),
        );
    }

    /**
     * @dataProvider provideArrayAccess
     */
    public function testArrayAccess($paths, $batchSize, $options)
    {
        $options = array_merge(array(
            // number of times we expect to call the getNodesByArray method
            'nb_fetches' => null,

            // node(s) paths we want to extract
            'target' => null,

            // if specified, iterate the RS this many times
            'iterate_result' => null,
        ), $options);

        $nbFetches = $options['nb_fetches'];
        $targets = (array) $options['target'];
        $iterateResult = $options['iterate_result'];

        $nodes = array();
        foreach ($paths as $path) {
            $node = $this->getNodeMock();
            $nodes[$path] = $node;
        }

        $this->objectManager->expects($this->exactly($nbFetches))
            ->method('getNodesByPathAsArray')
            ->will($this->returnCallback(function ($paths) use ($nodes) {
                $ret = array();
                foreach ($paths as $path) {
                    $ret[$path] = $nodes[$path];
                }

                return $ret;
            }));

        $nodes = new NodePathIterator($this->objectManager, $paths, null, null, $batchSize);

        if ($iterateResult) {
            for ($i = 0; $i < $iterateResult; $i++) {
                // if its not valid its at the end of the stack ... probably
                if (false === $nodes->valid()) {
                    continue;
                }
                $nodes->current($nodes);
                $nodes->next($nodes);
            }
        }

        $res = array();
        foreach ($targets as $target) {
            $res[$target] = $nodes[$target];
        }
    }
}
