<?php

namespace Jackalope\Query;

use Countable;
use SeekableIterator;
use OutOfBoundsException;

use Jackalope\ObjectManager;
use Jackalope\FactoryInterface;

/**
 * A NodeIterator object. Returned by QueryResult->getNodes().
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
        // OPTIMIZE: we could pre-fetch several nodes here, assuming the user wants more than one node
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

        return $this->objectmanager->getNode($path);
    }

    public function key()
    {
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
