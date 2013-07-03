<?php

namespace Jackalope;

use Jackalope\NodesPathIterator;
use PHPCR\NodeInterface;
use Jackalope\Node;
use Jackalope\Transport\NodeTypeFilterInterface;
use Jackalope\Transport\TransportInterface;

class NodesPathIteratorTest extends \PHPUnit_Framework_Testcase
{
    public function setUp()
    {
        $this->objectManager = $this->getMockBuilder('Jackalope\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->transport = $this->getMock('Jackalope\Transport\TransportInterface');
        $this->filterTransport = $this->getMockForAbstractClass('Jackalope\FilterableTestTransport');
    }

    public function provideIterator()
    {
        return array(
            // test batch sizes
            array(array('/foo1', '/foo2', '/foo3', '/foo4'), array(
                'batch_size' => 5,
                'transport_supports_filtering' => false, // @todo
                'type_filter' => null,
                'types' => array(),
            )),
            array(array('/foo1', '/foo2', '/foo3', '/foo4'), array(
                'batch_size' => 2,
                'transport_supports_filtering' => false,
                'type_filter' => null,
                'types' => array(),
            )),
            array(array('/foo1', '/foo2', '/foo3', '/foo4'), array(
                'batch_size' => 1,
                'transport_supports_filtering' => false,
                'type_filter' => null,
                'types' => array(),
            )),

            // test filtering
            array(array('/foo1', '/foo2', '/foo3', '/foo4'), array(
                'batch_size' => 2,
                'transport_supports_filtering' => false,
                'type_filter' => 'nt:foo',
                'types' => array('nt:foo', 'bt:bar', 'bt:foo', 'nt:gar'),
            )),

            array(array('/foo1', '/foo2', '/foo3', '/foo4'), array(
                'batch_size' => 2,
                'transport_supports_filtering' => true,
                'type_filter' => 'nt:foo',
                'types' => array('nt:foo', 'bt:bar', 'bt:foo', 'nt:gar'),
            )),

            array(array('/foo1', '/foo2', '/foo3', '/foo4'), array(
                'batch_size' => 2,
                'transport_supports_filtering' => true,
                'type_filter' => array('nt:foo', 'bt:bar'),
                'types' => array('nt:foo', 'bt:bar', 'bt:foo', 'nt:gar'),
            )),
        );
    }

    /**
     * @dataProvider provideIterator
     */
    public function testIterator($paths, $options = array())
    {
        // START: Initializiation
        //
        $typeFilter = $options['type_filter'];
        $types = (array) $options['types'];
        $expectedNodeCount = 0;

        if (!$typeFilter) {
            $expectedNodeCount = count($paths);
        } else {
            foreach ($types as $type) {
                if (in_array($type, (array) $typeFilter)) {
                    $expectedNodeCount++;
                 }
            }
        };

        // make key and value the same to emulate fetchPath => absPath
        $paths = array_combine(array_values($paths), $paths);

        $me = $this;
        $batchSize = $options['batch_size'];
        $nbBatches = (integer) ceil($expectedNodeCount / $batchSize);

        $transSupportFilt = $options['transport_supports_filtering'];

        // END: Initialization

        $transport = $transSupportFilt ? $this->filterTransport : $this->transport;
        $method = $transSupportFilt ? 'getNodesFiltered' : 'getNodes';

        $transport->expects($this->exactly($nbBatches))
            ->method($method)
            ->will($this->returnCallback(function () use (
                $me, $batchSize, $paths, $typeFilter, $types, $method
             ) {
                static $pending = null;
                if ($pending == null) {
                    $pending = count($paths);
                }
                $nodes = array();
                $count = count($paths) - $pending;

                $slicedPaths = array_slice($paths, count($paths) - $pending, $batchSize);

                foreach ($slicedPaths as $slicedPath) {
                    $pending--;

                    if ($typeFilter && $method == 'getNodesFiltered') {
                        $nodeType = $types[$count++];
                        if (!in_array($nodeType, (array) $typeFilter)) {
                            continue;
                        }
                    }

                    $nodes[$slicedPath] = new \stdClass;
                    if ($pending <= 0) {
                        break;
                    }
                }

                return $nodes;
            }));

        $this->objectManager->expects($this->any())
            ->method('getNodeByPath')
            ->will($this->returnCallback(function () use ($me, $typeFilter, $types, $transSupportFilt) {
                static $count = 0;
                $node = $me->getMockBuilder('Jackalope\Node')
                    ->disableOriginalConstructor()
                    ->getMock();

                if ($typeFilter && !$transSupportFilt) {
                    $nodeType = $types[$count];
                    $node->expects($this->once())
                        ->method('isNodeType')
                        ->will($this->returnCallback(function ($type) use ($nodeType) {
                            return $nodeType == $type;
                        }));
                }

                $count++;

                return $node;
            }));

        $npi = new NodesPathIterator(
            $this->objectManager, 
            $transport,
            $paths,
            $class = null,
            $typeFilter,
            $batchSize
        );

        // test batch size
        $this->assertEquals($batchSize, $npi->getBatchSize());
        $this->assertEquals($options['type_filter'], $npi->getTypeFilter());

        $objs = array();
        foreach ($npi as $obj) {
            $objs[] = $obj;
        }

        $this->assertCount($expectedNodeCount, $objs);
    }
}

abstract class FilterableTestTransport implements TransportInterface, NodeTypeFilterInterface
{
}
