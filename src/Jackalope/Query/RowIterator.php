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
     * @var integer
     */
    protected $position = 0;

    /**
     * Create the iterator.
     *
     * @param FactoryInterface $factory       the object factory
     * @param ObjectManager    $objectManager
     * @param array            $rows          Raw data as described in QueryResult and \Jackalope\Transport\TransportInterface
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
     * @return void
     *
     * @throws OutOfBoundsException
     */
    #[\ReturnTypeWillChange]
    public function seek($position)
    {
        $this->position = $position;

        if (!$this->valid()) {
            throw new OutOfBoundsException("invalid seek position ($position)");
        }
    }

    /**
     * @return integer
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->rows);
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->position = 0;
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        if (!$this->valid()) {
            return null;
        }

        return $this->factory->get(Row::class, [$this->objectManager, $this->rows[$this->position]]);
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->position;
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        ++$this->position;
    }

    /**
     * @return boolean
     */
    #[\ReturnTypeWillChange]
    public function valid()
    {
        return isset($this->rows[$this->position]);
    }
}
