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
class NodeIterator implements \Iterator
{
    protected $objectmanager;

    protected $results = array();

    public function __construct($factory, $results, $objectmanager)
    {
          $this->objectmanager = $objectmanager;
          $this->position = 0;
          $this->results = $results;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->results[$this->position]->getNode();
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