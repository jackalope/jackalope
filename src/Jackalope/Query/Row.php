<?php

namespace Jackalope\Query;

// inherit all doc
/**
 * {@inheritDoc}
 *
 * Jackalope: Contrary to most other Jackalope classes, the Row implements
 * Iterator and not IteratorAggregate to avoid overhead when iterating over a
 * row.
 *
 * @api
 */
class Row implements \Iterator, \PHPCR\Query\RowInterface
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
     * Columns of this result row: array of array with fields dcr:name and
     * dcr:value
     * @var array
     */
    protected $columns = array();
    /**
     * Which column we are on when iterating over the columns
     * @var integer
     */
    protected $position = 0;
    /**
     * The score this row has
     * @var float
     */
    protected $score = 0;
    /**
     * Cached list of values extracted from columns to avoid double work.
     * @var array
     * @see Row::getValues()
     */
    protected $values = array();

    /**
     * Create new Row instance.
     *
     * @param object $factory an object factory implementing "get" as
     *      described in \Jackalope\Factory
     * @param ObjectManager $objectManager
     * @param array $columns array of array with fields dcr:name and dcr:value
     */
    public function __construct($factory, $objectmanager, $columns)
    {
        $this->factory = $factory;
        $this->objectmanager = $objectmanager;
        foreach ($columns as $column) {
            if ($column['dcr:name'] == 'jcr:score') {
                $this->score = (float) $column['dcr:value'];
            } elseif (strlen($column['dcr:name']) >= 15
                      && substr($column['dcr:name'], -15) == 'jcr:primaryType'
                     ) {
                // ignore for now. TODO: trac the selector name in front of the primary type
            } else {
                $this->columns[] = $column;
                $this->values[$column['dcr:name']] = $column['dcr:value'];
            }
        }
    }

    // inherit all doc
    /**
     * @api
     */
    public function getValues()
    {
        return $this->values;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getValue($columnName)
    {
        $values = $this->getValues();
        if (array_key_exists($columnName, $values)) {
            return $values[$columnName];
        }

        throw new \PHPCR\ItemNotFoundException("Column :$columnName not found");
    }

    // inherit all doc
    /**
     * @api
     */
    public function getNode($selectorName = null)
    {
        // TODO: implement $selectorName

        return $this->objectmanager->getNode($this->getPath());
    }

    // inherit all doc
    /**
     * @api
     */
    public function getPath($selectorName = null)
    {
        // TODO: implement $selectorName

        return $this->getValue('jcr:path');
    }

    // inherit all doc
    /**
     * @api
     */
    public function getScore($selectorName = null)
    {
        // TODO: implement $selectorName
        return $this->score;
    }

    /**
     * Implement Iterator
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * Implement Iterator
     */
    public function current()
    {
        return $this->columns[$this->position]['dcr:value'];
    }

    /**
     * Implement Iterator
     */
    public function key()
    {
        return $this->columns[$this->position]['dcr:name'];
    }

    /**
     * Implement Iterator
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * Implement Iterator
     */
    public function valid()
    {
        return isset($this->columns[$this->position]);
    }
}
