<?php

namespace Jackalope\Query;

use Jackalope\ObjectManager;
use Jackalope\NotImplementedException;
use Jackalope\Factory;

use PHPCR\Query\QueryResultInterface;

use IteratorAggregate;

/**
 * @api
 */
class QueryResult implements IteratorAggregate, QueryResultInterface
{
    /**
     * @var \Jackalope\ObjectManager
     */
    protected $objectmanager;

    /**
     * @var \Jackalope\Factory
     */
    protected $factory;

    /**
     * Storing the query result raw data in the format documented at
     * \Jackalope\Transport\QueryInterface::query()
     * @var array
     */
    protected $rows = array();

    /**
     * Create a new query result from raw data from transport.
     *
     * The raw data format is documented in
     * \Jackalope\Transport\QueryInterface::query()
     *
     * @param object $factory an object factory implementing "get" as
     *      described in \Jackalope\Factory
     * @param array $rawData the data as returned by the transport
     * @param ObjectManager $objectManager
     */
    public function __construct(Factory $factory, $rawData, ObjectManager $objectmanager)
    {
        $this->factory = $factory;
        $this->rows = $rawData;
        $this->objectmanager = $objectmanager;
    }

    /**
     * Implement the IteratorAggregate interface and returns exactly the same
     * iterator as QueryResult::getRows()
     *
     * @return Iterator implementing <b>SeekableIterator</b> and <b>Countable</b>.
     *      Keys are the row position in this result set, Values are the
     *      RowInterface instances.
     *
     * @throws \PHPCR\RepositoryException if this call is the second time
     *      getIterator(), getRows() or getNodes() has been called on the same
     *      QueryResult object or if another error occurs.
     *
     * @api
     */
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
        $columnNames = array();

        foreach ($this->rows as $row) {
            foreach ($row as $columns) {
                $columnNames[] = $columns['dcr:name'];
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
        return $this->factory->get('Query\RowIterator', array($this->objectmanager, $this->rows));
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getNodes($prefetch = false)
    {
        if ($prefetch !== true) {
            return $this->factory->get('Query\NodeIterator', array($this->objectmanager, $this->rows));
        }

        $paths = array();
        foreach ($this->rows as $row) {
            foreach ($row as $column) {
                if ('jcr:path' === $column['dcr:name']) {
                    $paths[] = $column['dcr:value'];
                }
            }
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
        $selectorNames = array();

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
