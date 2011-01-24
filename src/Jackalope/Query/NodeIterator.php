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
class NodeIterator implements \SeekableIterator, \Countable
{
    protected $objectmanager;

    protected $rows;

    protected $position = 0;

    public function __construct($objectmanager, $rows)
    {
          $this->objectmanager = $objectmanager;
          $this->rows = $rows;
    }

    public function seek($nodeName)
    {
        foreach ($this->rows[$this->position] as $position => $column) {
            if ($column['dcr:name'] == 'jcr:path') {
                if ($column['dcr:value'] == $nodeName) {
                    $this->position = $position;
                }
            }
        }

        if (!$this->valid()) {
            throw new \OutOfBoundsException("invalid seek position ($position)");
        }
    }

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
        foreach ($this->rows[$this->position] as $column) {
            if ($column['dcr:name'] == 'jcr:path') {
                $path = $column['dcr:value'];
            }
        }

        return $this->objectmanager->getNode($path);
    }

    public function key()
    {
        foreach ($this->rows[$this->position] as $column) {
            if ($column['dcr:name'] == 'jcr:path') {
                $path = $column['dcr:value'];
            }
        }

        return $path;
    }

    public function next()
    {
        ++$this->position;
    }

    public function valid()
    {
        return isset($this->rows[$this->position]);
    }
}