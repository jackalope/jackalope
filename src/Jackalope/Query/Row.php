<?php

namespace Jackalope\Query;

/**
 * A row in the query result table.
 *
 * The \Traversable interface enables the implementation to be addressed with
 * <b>foreach</b>. Rows have to implement either \RecursiveIterator or
 * \Iterator.
 * The iterator is similar to <b>getValues()</b> with keys being the column
 * names and the values the corresponding entry in that column for this row.
 *
 * @package phpcr
 * @subpackage interfaces
 * @api
 */
class Row implements \Iterator, \PHPCR\Query\RowInterface
{

    protected $data = array();

    public function __construct($factory, $data, $objectmanager)
    {
          $this->objectmanager = $objectmanager;
          $this->position = 0;
          $this->data = $data;
    }

    /**
     * Returns an array of all the values in the same order as the column names
     * returned by QueryResult.getColumnNames().
     *
     * @return array List of values of each column of the current result row.
     *
     * @throws \PHPCR\RepositoryException if an error occurs
     * @api
     */
    public function getValues()
    {
        $array = array();
        foreach ($this->data as $row) {
            $array[$row['key']] = $row['value'];
        }
        return $array;
    }

    /**
     * Returns the value of the indicated column in this Row.
     *
     * @param string $columnName name of query result table column
     * @return mixed The value of the given column of the current result row.
     *
     * @throws \PHPCR\ItemNotFoundException if columnName s not among the column names of the query result table.
     * @throws \PHPCR\RepositoryException if another error occurs.
     * @api
     */
    public function getValue($columnName)
    {
        foreach ($this->data as $key => $entry) {
            if ($entry['key'] === $columnName) {
                return $entry['value'];
            }
        }
        throw new PHPCR\ItemNotFoundException("Column :$columnName not found");
    }

    /**
     * Returns the Node corresponding to this Row and the specified selector, if given.
     *
     * @param string $selectorName The selector identifying a node within the current result row.
     * @return \PHPCR\NodeInterface a Node
     *
     * @throws \PHPCR\RepositoryException If selectorName is not the alias of a selector in this query or if
     *                                    another error occurs.
     * @api
     */
    public function getNode($selectorName = NULL)
    {
        $path = $this->getValue('jcr:path');
        return $this->objectmanager->getNode($path);
    }

    /**
     * Get the path of a node identified by a selector.
     *
     * Equivalent to Row.getNode(selectorName).getPath(). However, some
     * implementations may be able gain efficiency by not resolving the actual Node.
     *
     * @param string $selectorName The selector identifying a node within the current result row.
     * @return string The path representing the node identified by the given selector.
     *
     * @throws \PHPCR\RepositoryException if selectorName is not the alias of a selector in this query or
     *                                    if another error occurs.
     * @api
     */
    public function getPath($selectorName = NULL)
    {
        return $this->data['jcr:path'];
    }

    /**
     * Returns the full text search score for this row associated with the specified
     * selector.
     *
     * This corresponds to the score of a particular node.
     * If no selectorName is given, the default selector is used.
     * If no FullTextSearchScore AQM object is associated with the selector
     * selectorName this method will still return a value. However, in that case
     * the returned value may not be meaningful or may simply reflect the minimum
     * possible relevance level (for example, in some systems this might be a s
     * core of 0).
     *
     * Note, in JCR-SQL2 a FullTextSearchScore AQM object is represented by a
     * SCORE() function. In JCR-JQOM it is represented by a Java object of type
     * \PHPCR\Query\QOM\FullTextSearchScoreInterface.
     *
     * @param string $selectorName The selector identifying a node within the current result row.
     * @return float The full text search score for this row.
     *
     * @throws \PHPCR\RepositoryException if selectorName is not the alias of a selector in this query or
     *                                    (in case of no given selectorName) if this query has more than one
     *                                    selector (and therefore, this Row corresponds to more than one Node)
     *                                    or if another error occurs.
     * @api
     */
    public function getScore($selectorName = NULL)
    {
        $this->data['jcr:score'];
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->data[$this->position]['value'];
    }

    public function key()
    {
        return $this->data[$this->position]['key'];
    }

    public function next()
    {
        ++$this->position;
    }

    public function valid()
    {
        return isset($this->data[$this->position]);
    }

}
