<?php

namespace Jackalope\Query;

use Jackalope\FactoryInterface;
use Jackalope\ObjectManager;
use PHPCR\Query\RowInterface;

/**
 * Iterator to efficiently iterate over the raw query result.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
final class RowIterator implements \SeekableIterator, \Countable
{
    private ObjectManager $objectManager;
    private FactoryInterface $factory;
    private array $rows;
    private int $offset = 0;

    /**
     * @param array<array<string, mixed>> $rows Raw data as described in QueryResult and \Jackalope\Transport\TransportInterface
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
    public function seek($offset): void
    {
        $this->offset = $offset;

        if (!$this->valid()) {
            throw new \OutOfBoundsException("invalid seek position ($offset)");
        }
    }

    public function count(): int
    {
        return count($this->rows);
    }

    public function rewind(): void
    {
        $this->offset = 0;
    }

    public function current(): ?RowInterface
    {
        if (!$this->valid()) {
            return null;
        }

        return $this->factory->get(Row::class, [$this->objectManager, $this->rows[$this->offset]]);
    }

    public function key(): int
    {
        return $this->offset;
    }

    public function next(): void
    {
        ++$this->offset;
    }

    public function valid(): bool
    {
        return isset($this->rows[$this->offset]);
    }
}
