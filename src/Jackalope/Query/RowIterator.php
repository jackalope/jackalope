<?php

namespace Jackalope\Query;

use Countable;
use SeekableIterator;
use OutOfBoundsException;

use Jackalope\ObjectManager;
use Jackalope\FactoryInterface;

/**
 * Iterator to efficiently iterate over the raw query result.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
class RowIterator implements SeekableIterator, Countable
{
    protected $objectmanager;

    protected $factory;

    protected $rows;

    protected $position = 0;

    /**
     * Create the iterator.
     *
     * @param FactoryInterface $factory       the object factory
     * @param ObjectManager    $objectManager
     * @param array            $rows          Raw data as described in QueryResult and \Jackalope\Transport\TransportInterface
     */
    public function __construct(FactoryInterface $factory, ObjectManager $objectmanager, $rows)
    {
        $this->factory = $factory;
        $this->objectmanager = $objectmanager;
        $this->rows = $rows;
    }

    public function seek($position)
    {
        $this->position = $position;

        if (!$this->valid()) {
            throw new OutOfBoundsException("invalid seek position ($position)");
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
        if (!$this->valid()) {
            return null;
        }

        return $this->factory->get('Query\Row', array($this->objectmanager, $this->rows[$this->position]));
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
