<?php

namespace Jackalope\Query;

use Jackalope\ObjectManager;
use Jackalope\FactoryInterface;

use PHPCR\Query\QueryResultInterface;

use IteratorAggregate;

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
     * @param FactoryInterface $factory       the object factory
     * @param array            $rawData       the data as returned by the transport
     * @param ObjectManager    $objectManager
     */
    public function __construct(FactoryInterface $factory, $rawData, ObjectManager $objectmanager)
    {
        $this->factory = $factory;
        $this->rows = $rawData;
        $this->objectmanager = $objectmanager;
    }

    /**
     * Implement the IteratorAggregate interface and returns exactly the same
     * iterator as QueryResult::getRows()
     *
     * @return \Iterator implementing <b>SeekableIterator</b> and <b>Countable</b>.
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
                if ('jcr:path' != substr($columns['dcr:name'], -8)
                    && 'jcr:score' != substr($columns['dcr:name'], -9)
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
