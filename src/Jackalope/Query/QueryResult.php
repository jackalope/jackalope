<?php
namespace Jackalope\Query;

use Jackalope\ObjectManager, Jackalope\NotImplementedException;

/**
 * A QueryResult object. Returned by Query->execute().
 *
 * The \Traversable interface enables the implementation to be addressed with
 * <b>foreach</b>. QueryResults have to implement either \RecursiveIterator or
 * \Iterator.
 * The iterator is equivalent to <b>getRows()</b> returning a list of the rows.
 * The iterator keys have no significant meaning.
 * Note: We use getRows and not getNodes as this is more generic. If you have a
 * single selector, you can either do foreach on getNodes or call getNode on the
 * rows.
 *
 * @api
 */
class QueryResult implements \IteratorAggregate, \PHPCR\Query\QueryResultInterface
{
    protected $objectmanager;

    protected $factory;

    protected $rows = array();

    public function __construct($factory, $rawData, $objectmanager)
    {
        $this->factory = $factory;
        $this->rows = $rawData;
        $this->objectmanager = $objectmanager;
    }

    /**
     * Returns an iterator over the Rows of the result table.
     *
     * The rows are returned according to the ordering specified in the query.
     *
     * @return Iterator implementing <b>SeekableIterator</b> and <b>Countable</b>.
     *                  Keys are the row position in this result set, Values are the RowInterface instances.
     * @throws \PHPCR\RepositoryException if this call is the second time either getRows() or getNodes()
     *                                    has been called on the same QueryResult object or if another error occurs.
     * @api
    */
    public function getIterator()
    {
        return $this->getRows();
    }

    /**
     * Returns an array of all the column names in the table view of this result set.
     *
     * @return array A list holding the column names.
     *
     * @throws \PHPCR\RepositoryException if an error occurs.
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
     * Returns an iterator over the Rows of the result table.
     *
     * The rows are returned according to the ordering specified in the query.
     *
     * @return Iterator implementing <b>SeekableIterator</b> and <b>Countable</b>.
     *                  Keys are the row position in this result set, Values are the RowInterface instances.
     * @throws \PHPCR\RepositoryException if this call is the second time either getRows() or getNodes()
     *                                    has been called on the same QueryResult object or if another error occurs.
     * @api
    */
    public function getRows()
    {
        return $this->factory->get('Query\RowIterator', array($this->objectmanager, $this->rows));
    }

    /**
     * Returns an iterator over all nodes that match the query.
     *
     * @param  bool|int $prefetch If to prefetch or not
     *                              int < 0/true means all, 0/false means none, int > 0 means the prefetch chunk size or none
     * @return Iterator implementing <b>SeekableIterator</b> and <b>Countable</b>.
     *                  Keys are the paths, Values the given Node instances.
     *
     * @throws \PHPCR\RepositoryException if the query contains more than one selector, if this call is
     *                                    the second time either getRows() or getNodes() has been called on the
     *                                    same QueryResult object or if another error occurs.
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
     * Returns an array of all the selector names that were used in the query
     * that created this result.
     *
     * If the query did not have a selector name then an empty array is returned.
     *
     * @return array A String array holding the selector names.
     *
     * @throws \PHPCR\RepositoryException if an error occurs.
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
