<?php

namespace Jackalope\Query;

use Jackalope\FactoryInterface;
use Jackalope\ObjectManager;

/**
 * Iterator to efficiently iterate over the raw query result.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
class RowIterator implements \SeekableIterator, \Countable
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var FactoryInterface
     */
    protected $factory;

    /**
     * @var array
     */
    protected $rows;

    /**
     * @var int
     */
    protected $position = 0;

    /**
     * Create the iterator.
     *
     * @param FactoryInterface $factory the object factory
     * @param array            $rows    Raw data as described in QueryResult and \Jackalope\Transport\TransportInterface
     */
    public function __construct(FactoryInterface $factory, ObjectManager $objectManager, $rows)
    {
        $this->factory = $factory;
        $this->objectManager = $objectManager;
        $this->rows = $rows;
    }

    /**
     * @param int $position
     *
     * @throws \OutOfBoundsException
     */
    public function seek($position)
    {
        $this->position = $position;

        if (!$this->valid()) {
            throw new \OutOfBoundsException("invalid seek position ($position)");
        }
    }

    /**
     * @return int
     */
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

        return $this->factory->get(Row::class, [$this->objectManager, $this->rows[$this->position]]);
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return isset($this->rows[$this->position]);
    }
}
