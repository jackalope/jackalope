<?php

namespace Jackalope\Query;

use Jackalope\ObjectManager, Jackalope\NotImplementedException;

/**
 * A NodeIterator object. Returned by QueryResult->getNodes().
 */
class NodeIterator implements \SeekableIterator, \Countable
{
    protected $objectmanager;

    protected $factory;

    protected $rows;

    protected $position = 0;

    public function __construct($factory, $objectmanager, $rows)
    {
        $this->objectmanager = $objectmanager;
        $this->factory = $factory;
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

        throw new \OutOfBoundsException("invalid seek position ($nodeName)");
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
        // TODO: add a default for $path or handle case when no $path is found
        foreach ($this->rows[$this->position] as $column) {
            if ($column['dcr:name'] == 'jcr:path') {
                $path = $column['dcr:value'];
            }
        }

        return $this->objectmanager->getNode($path);
    }

    public function key()
    {
        // TODO: add a default for $path or handle case when no $path is found
        foreach ($this->rows[$this->position] as $column) {
            if ($column['dcr:name'] == 'jcr:path') {
                $path = $column['dcr:value'];
            }
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
