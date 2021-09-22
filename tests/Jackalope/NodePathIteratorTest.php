<?php

namespace Jackalope;

use PHPUnit\Framework\MockObject\MockObject;

class NodePathIteratorTest extends TestCase
{
    /**
     * @var ObjectManager&MockObject
     */
    private ObjectManager $objectManager;

    public function setUp(): void
    {
        $this->objectManager = $this->createMock(ObjectManager::class);
    }

    public function provideIterator(): array
    {
        return [
            [['/foo1'], 'Node', 'nt:foo', 2],
            [['/foo1', '/foo2', '/foo3', '/foo4'], 'Node', 'nt:foo', 2],
            [['/foo1', '/foo2', '/foo3', '/foo4', '/foo5'], 'Node', 'nt:foo', 2],
            [['/foo1', '/foo2', '/foo3', '/foo4'], 'Node', 'nt:foo', 10],
        ];
    }

    /**
     * @dataProvider provideIterator
     */
    public function testIterator($paths, $class, $filter, $batchSize): void
    {
        $me = $this;
        $nbBatches = (int) ceil(count($paths) / $batchSize);
        $this->objectManager->expects($this->exactly($nbBatches))
            ->method('getNodesByPathAsArray')
            ->willReturnCallback(function (
                $cPaths,
                $cClass,
                $cFilter
            ) use (
                $me,
                $class,
                $filter,
                $batchSize
            ) {
                $me->assertLessThanOrEqual($batchSize, count($cPaths));
                $nodes = [];
                $me->assertEquals($class, $cClass);
                $me->assertEquals($filter, $cFilter);
                foreach ($cPaths as $cPath) {
                    $nodes[$cPath] = $me->getNodeMock();
                }

                return $nodes;
            });

        $nodes = new NodePathIterator($this->objectManager, $paths, $class, $filter, $batchSize);

        foreach ($nodes as $node) {
            $this->assertInstanceOf(Node::class, $node);
        }
    }

    public function provideArrayAccess(): array
    {
        return [
            // 1st target, batch size 2, 1 fetch
            [
                ['p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7', 'p8'],
                2,
                ['nb_fetches' => 1, 'target' => 'p1'],
            ],

            // 3rd target, batch size 2, 2 fetches
            [
                ['p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7', 'p8'],
                2,
                ['nb_fetches' => 2, 'target' => 'p3'],
            ],

            // 3rd target, batch size 1, 3 fetches
            [
                ['p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7', 'p8'],
                1,
                ['nb_fetches' => 3, 'target' => 'p3'],
            ],

            // test 0 paths
            [
                [],
                2,
                ['nb_fetches' => 0],
            ],

            // test partial iteration
            [
                ['p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7', 'p8'],
                2,
                ['nb_fetches' => 2, 'target' => 'p4', 'iterate_result' => 3],
            ],
            [
                ['p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7', 'p8'],
                2,
                ['nb_fetches' => 4, 'target' => 'p4', 'iterate_result' => 8],
            ],

            // multiple targets
            [
                ['p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7', 'p8'],
                2,
                ['nb_fetches' => 3, 'target' => ['p1', 'p2', 'p3', 'p4', 'p5']],
            ],
            [
                ['p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7', 'p8'],
                2,
                ['nb_fetches' => 4, 'target' => ['p8', 'p1']],
            ],
            [
                ['p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7', 'p8'],
                100,
                ['nb_fetches' => 1, 'target' => ['p8', 'p1']],
            ],
        ];
    }

    /**
     * @dataProvider provideArrayAccess
     */
    public function testArrayAccess($paths, $batchSize, $options): void
    {
        $options = array_merge([
            // number of times we expect to call the getNodesByArray method
            'nb_fetches' => null,

            // node(s) paths we want to extract
            'target' => null,

            // if specified, iterate the RS this many times
            'iterate_result' => null,
        ], $options);

        $nbFetches = $options['nb_fetches'];
        $targets = (array) $options['target'];
        $iterateResult = $options['iterate_result'];

        $nodes = [];
        foreach ($paths as $path) {
            $node = $this->getNodeMock();
            $nodes[$path] = $node;
        }

        $this->objectManager->expects($this->exactly($nbFetches))
            ->method('getNodesByPathAsArray')
            ->willReturnCallback(function ($paths) use ($nodes) {
                $ret = [];
                foreach ($paths as $path) {
                    $ret[$path] = $nodes[$path];
                }

                return $ret;
            });

        $nodes = new NodePathIterator($this->objectManager, $paths, Node::class, null, $batchSize);

        if ($iterateResult) {
            for ($i = 0; $i < $iterateResult; ++$i) {
                // if its not valid its at the end of the stack ... probably
                if (false === $nodes->valid()) {
                    continue;
                }
                $nodes->current($nodes);
                $nodes->next($nodes);
            }
        }

        foreach ($targets as $target) {
            $nodes[$target];
        }
    }

    /**
     * Count should not need any interaction with the object manager.
     */
    public function testCount(): void
    {
        $nodes = [];
        $nodes2 = [];
        foreach (['p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7'] as $name) {
            $nodes[$name] = $this->getNodeMock();
        }
        foreach (['p8'] as $name) {
            $nodes2[$name] = $this->getNodeMock();
        }

        $this->objectManager
            ->method('getNodesByPathAsArray')
            ->withConsecutive([['p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7']], [['p8']])
            ->willReturnOnConsecutiveCalls($this->returnValue($nodes), $this->returnValue($nodes2))
        ;

        $iterator = new NodePathIterator($this->objectManager, ['p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7', 'p8'], Node::class, null, 7);
        $this->assertCount(8, $iterator);
    }

    public function testSeek(): void
    {
        $nodes = [];
        $nodes2 = [];
        foreach (['p1', 'p2', 'p3'] as $name) {
            $nodes[$name] = $this->getNodeMock();
        }
        foreach (['p8', 'p9'] as $name) {
            $nodes2[$name] = $this->getNodeMock();
        }

        $this->objectManager
            ->method('getNodesByPathAsArray')
            ->withConsecutive([['p1', 'p2', 'p3']], [['p8', 'p9']])
            ->willReturnOnConsecutiveCalls($this->returnValue($nodes), $this->returnValue($nodes2))
        ;

        $iterator = new NodePathIterator($this->objectManager, ['p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7', 'p8', 'p9'], Node::class, null, 3);
        $iterator->seek(7);
        $iterator->valid();

        $this->assertEquals($nodes2['p8'], $iterator->current());
    }
}
