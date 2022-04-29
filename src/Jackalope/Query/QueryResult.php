<?php

namespace Jackalope\Query;

use Iterator;
use IteratorAggregate;
use Jackalope\FactoryInterface;
use Jackalope\ObjectManager;
use PHPCR\Query\QueryResultInterface;
use PHPCR\RepositoryException;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
class QueryResult implements IteratorAggregate, QueryResultInterface
{
    /**
     * @var ObjectManager
     */
    protected $objectmanager;

    /**
     * @var FactoryInterface
     */
    protected $factory;

    /**
     * Storing the query result raw data.
     *
     * @see QueryInterface::query()
     *
     * @var array
     */
    protected $rows = [];

    /**
     * Create a new query result from raw data from transport.
     *
     * @see QueryInterface::query() The raw data format
     *
     * @param FactoryInterface $factory the object factory
     * @param array            $rawData the data as returned by the transport
     */
    public function __construct(FactoryInterface $factory, $rawData, ObjectManager $objectManager)
    {
        $this->factory = $factory;
        $this->rows = $rawData;
        $this->objectmanager = $objectManager;
    }

    /**
     * Implement the IteratorAggregate interface and returns exactly the same
     * iterator as QueryResult::getRows().
     *
     * @return Iterator implementing <b>SeekableIterator</b> and <b>Countable</b>.
     *                  Keys are the row position in this result set, Values are the
     *                  RowInterface instances.
     *
     * @throws RepositoryException if this call is the second time
     *                             getIterator(), getRows() or getNodes() has been called on the same
     *                             QueryResult object or if another error occurs
     *
     * @api
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return $this->getRows();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getColumnNames()
    {
        $columnNames = [];

        foreach ($this->rows as $row) {
            foreach ($row as $columns) {
                if ('jcr:path' !== substr($columns['dcr:name'], -8)
                    && 'jcr:score' !== substr($columns['dcr:name'], -9)
                ) {
                    // skip the meta information path and score that is also in the raw result table
                    $columnNames[] = $columns['dcr:name'];
                }
            }
        }

        return array_unique($columnNames);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getRows()
    {
        return $this->factory->get(RowIterator::class, [$this->objectmanager, $this->rows]);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getNodes($prefetch = false)
    {
        if (true !== $prefetch) {
            return $this->factory->get(NodeIterator::class, [$this->objectmanager, $this->rows]);
        }

        $paths = [];

        foreach ($this->getRows() as $row) {
            $paths[] = $row->getPath();
        }

        return $this->objectmanager->getNodesByPath($paths);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getSelectorNames()
    {
        $selectorNames = [];

        foreach ($this->rows as $row) {
            foreach ($row as $column) {
                if (array_key_exists('dcr:selectorName', $column)) {
                    $selectorNames[] = $column['dcr:selectorName'];
                }
            }
        }

        return array_unique($selectorNames);
    }
}
