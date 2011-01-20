<?php
namespace Jackalope\Query;

use Jackalope\ObjectManager, Jackalope\NotImplementedException;

/**
 * A QueryResult object. Returned by Query->execute().
 *
 * The \Traversable interface enables the implementation to be addressed with
 * <b>foreach</b>. QueryResults have to implement einther \RecursiveIterator or
 * \Iterator.
 * The iterator is equivalent to <b>getRows()</b> returning a list of the rows.
 * The iterator keys have no significant meaning.
 * Note: We use getRows and not getNodes as this is more generic. If you have a
 * single selector, you can either do foreach on getNodes or call getNode on the
 * rows.
 *
 * @package phpcr
 * @subpackage interfaces
 * @api
 */
class QueryResult implements \Iterator, \PHPCR\Query\QueryResultInterface
{
    protected $objectmanager;

    protected $factory;

    protected $results;

    public function __construct($factory, $rawData, $objectmanager)
    {
        $this->objectmanager = $objectmanager;
        $this->position = 0;
        $this->factory = $factory;
        $dom = new \DOMDocument();
        $dom->loadXML($rawData);
        $r = 0;
        $resultArray = array();
        foreach ($dom->getElementsByTagName('search-result-property') as $result) {
            // <search-result-property>
            foreach($result->childNodes as $column) {
            // <column>
            $rowData = array();
            $i = 0;
                foreach($result->childNodes as $data) {
                    $key = $data->getElementsByTagName('name')->item(0)->nodeValue;
                    $value = $data->getElementsByTagName('value')->item(0)->nodeValue;
                    $rowData[$i]['key'] = $key;
                    $rowData[$i]['value'] = $value;
                    $i++;
                }
            }
            $obj = $queryResult = $factory->get(
                 'Query\Row',
                 array(
                     $rowData,
                     $this->objectmanager,
                 )
            );
            $resultArray[$r] = $obj;
            $r++;
        }
        $this->results = $resultArray;
    }

    /**
     * Returns an array of all the column names in the table view of this result set.
     *
     * @return array A list holding the column names.
     *
     * @throws \PHPCR\RepositoryException if an error occurs.
     * @api
     */
    public function getColumnNames() {
     $array = array();
        foreach ($this->results as $row) {
             $array = array_merge($array, array_keys($row->getValues()));
        }
        return array_unique($array);
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
        return $this;
    }

    /**
     * Returns an iterator over all nodes that match the query.
     *
     * The nodes are returned according to the ordering specified in the query.
     *
     * @return Iterator implementing <b>SeekableIterator</b> and <b>Countable</b>.
     *                  Keys are the Node names, values the corresponding NodeInterface instances.
     *
     * @throws \PHPCR\RepositoryException if the query contains more than one selector, if this call is
     *                                    the second time either getRows() or getNodes() has been called on the
     *                                    same QueryResult object or if another error occurs.
     * @api
     */
    public function getNodes() {
        return $obj = $queryResult = $this->factory->get(
            'Query\NodeIterator',
             array(
                 $this->results,
                 $this->objectmanager,
            )
        );
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
        $selectors = array();
        //TODO: Fill $selectors with the selectors
        return $selectors;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->results[$this->position];
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
        return isset($this->results[$this->position]);
    }

}