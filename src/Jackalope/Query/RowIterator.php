<?php

namespace Jackalope\Query;

use Jackalope\ObjectManager, Jackalope\NotImplementedException;

class RowIterator implements \SeekableIterator, \Countable
{
    protected $objectmanager;

    protected $rows;

    protected $position = 0;

    public function __construct($objectmanager, $rows)
    {
        $this->objectmanager = $objectmanager;

        foreach ($rows as $row) {
            $this->rows[] = new Row($this->objectmanager, $row);
        }
    }

    public function seek($position)
    {
        $this->position = $position;

        if (!$this->valid()) {
            throw new \OutOfBoundsException("invalid seek position ($position)");
        }
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
        return $this->rows[$this->position];
    }

    public function key()
    {
        return $this->position;
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
