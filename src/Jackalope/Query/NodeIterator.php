<?php

namespace Jackalope\Query;

use Countable;
use SeekableIterator;
use OutOfBoundsException;

use Jackalope\ObjectManager;
use Jackalope\FactoryInterface;

/**
 * Lazy loading iterator for QueryResult->getNodes() that delays fetching the
 * node to the last possible moment.
 *
 * OPTIMIZE: The iterator could prefetch a couple of nodes at a time to reduce
 * the number of storage round-trips while still not loading all nodes at once.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
class NodeIterator implements SeekableIterator, Countable
{
    /**
     * @var ObjectManager
     */
    protected $objectmanager;

    /**
     * @var FactoryInterface
     */
    protected $factory;

    protected $rows;

    protected $position = 0;

    /**
     * @param FactoryInterface $factory       the object factory
     * @param ObjectManager    $objectmanager
     * @param array            $rows
     */
    public function __construct(FactoryInterface $factory, ObjectManager $objectmanager, $rows)
    {
        $this->factory = $factory;
        $this->objectmanager = $objectmanager;
        $this->rows = $rows;
    }

    public function seek($nodeName)
    {
        foreach ($this->rows as $position => $columns) {
            foreach ($columns as $column) {
                if ($column['dcr:name'] == 'jcr:path') {
                    if ($column['dcr:value'] == $nodeName) {
                        $this->position = $position;

                        return;
                    }
                }
            }
        }

        throw new OutOfBoundsException("invalid seek position ($nodeName)");
    }

    public function count()
    {
        return count($this->rows);
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        $path = $this->key();
        if (!isset($path)) {
            return null;
        }

        return $this->objectmanager->getNodeByPath($path);
    }

    public function key()
    {
        if (!$this->valid()) {
            return null;
        }

        foreach ($this->rows[$this->position] as $column) {
            if ($column['dcr:name'] == 'jcr:path') {
                $path = $column['dcr:value'];
                break;
            }
        }

        if (!isset($path)) {
            return null;
        }

        return $path;
    }

    public function next()
    {
        ++$this->position;
    }

    public function valid()
    {
        return isset($this->rows[$this->position]);
    }
}
