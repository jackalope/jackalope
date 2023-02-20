<?php

namespace Jackalope\Query;

use Jackalope\FactoryInterface;
use Jackalope\ObjectManager;
use PHPCR\ItemNotFoundException;
use PHPCR\NodeInterface;
use PHPCR\Query\RowInterface;
use PHPCR\RepositoryException;

/**
 * {@inheritDoc}
 *
 * Jackalope: Contrary to most other Jackalope classes, the Row implements
 * Iterator and not IteratorAggregate to avoid overhead when iterating over a
 * row.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
final class Row implements \Iterator, RowInterface
{
    private ObjectManager $objectManager;

    /**
     * Columns of this result row: array of array with fields dcr:name and dcr:value.
     */
    private array $columns = [];

    /**
     * Which column we are on when iterating over the columns.
     */
    private int $position = 0;

    /**
     * The score this row has for each selector.
     *
     * @var float[]
     */
    private array $score = [];

    /**
     * The path to the node for each selector.
     *
     * @var string[]
     */
    private array $path = [];

    /**
     * Cached list of values extracted from columns to avoid double work.
     *
     * @see Row::getValues()
     */
    private array $values = [];

    private string $defaultSelectorName;

    /**
     * Create new Row instance.
     *
     * @param FactoryInterface $factory the object factory
     * @param array            $columns array of array with fields dcr:name and dcr:value
     */
    public function __construct(FactoryInterface $factory, ObjectManager $objectManager, array $columns)
    {
        $this->objectManager = $objectManager;

        // TODO all of the normalization logic should better be moved to the Jackrabbit transport layer
        foreach ($columns as $column) {
            $pos = strpos($column['dcr:name'], '.');
            if (false !== $pos) {
                // jackalope-doctrine-dbal has the selector name both in the dcr:name and as separate column dcr:selectorName
                $selectorName = substr($column['dcr:name'], 0, $pos);
                $column['dcr:name'] = substr($column['dcr:name'], $pos + 1);
            } elseif (isset($column['dcr:selectorName'])) {
                $selectorName = $column['dcr:selectorName'];
            } else {
                $selectorName = '';
            }

            if ('jcr:score' === $column['dcr:name']) {
                $this->score[$selectorName] = (float) $column['dcr:value'];
            } elseif ('jcr:path' === $column['dcr:name']) {
                $this->path[$selectorName] = $column['dcr:value'];
            } else {
                $this->columns[] = $column;
                $this->values[$selectorName][$column['dcr:name']] = $column['dcr:value'];
            }
        }

        $this->defaultSelectorName = key($this->path);

        if (isset($this->values[''])) {
            foreach ($this->values[''] as $key => $value) {
                $this->values[$this->defaultSelectorName][$key] = $value;
            }
            unset($this->values['']);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getValues(): array
    {
        $values = [];
        foreach ($this->values as $selectorName => $columns) {
            foreach ($columns as $key => $value) {
                $values[$selectorName.'.'.$key] = $value;
            }
        }

        return $values;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getValue($columnName)
    {
        if (false === strpos($columnName, '.')) {
            $columnName = $this->defaultSelectorName.'.'.$columnName;
        }

        $values = $this->getValues();
        if (!array_key_exists($columnName, $values)) {
            throw new ItemNotFoundException("Column '$columnName' not found");
        }

        $value = $values[$columnName];

        // According to JSR-283 6.7.39 a query should only return
        // single-valued properties. We join the values when it's a string
        // for multi-values boolean/binary values we can't provide a
        // defined result so we return null
        if (is_array($value)) {
            if (count($value) && is_scalar($value[0]) && !is_bool($value[0])) {
                $value = implode(' ', $value);
            } else {
                $value = null;
            }
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getNode($selectorName = null): ?NodeInterface
    {
        $path = $this->getPath($selectorName);
        if (!$path) {
            // handle outer joins
            return null;
        }

        return $this->objectManager->getNodeByPath($this->getPath($selectorName));
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getPath($selectorName = null): ?string
    {
        if (null === $selectorName) {
            $selectorName = $this->defaultSelectorName;
        }

        // do not use isset, the path might be null on outer joins
        if (!array_key_exists($selectorName, $this->path)) {
            throw new RepositoryException('Attempting to get the path for a non existent selector: '.$selectorName);
        }

        return $this->path[$selectorName];
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getScore($selectorName = null): float
    {
        if (null === $selectorName) {
            $selectorName = $this->defaultSelectorName;
        }

        if (!array_key_exists($selectorName, $this->score)) {
            throw new RepositoryException('Attempting to get the score for a non existent selector: '.$selectorName);
        }

        return $this->score[$selectorName];
    }

    /**
     * Implement Iterator.
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * Implement Iterator.
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->columns[$this->position]['dcr:value'];
    }

    /**
     * Implement Iterator.
     */
    public function key(): ?string
    {
        return $this->columns[$this->position]['dcr:name'];
    }

    /**
     * Implement Iterator.
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * Implement Iterator.
     *
     * @return bool whether the current position is valid
     */
    public function valid(): bool
    {
        return isset($this->columns[$this->position]);
    }
}
