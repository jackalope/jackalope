<?php

namespace Jackalope;

use Jackalope\Node;

/**
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
class NodePathIterator implements \Iterator, \ArrayAccess
{
    protected $position = 0;
    protected $nodes = array();
    protected $paths;
    protected $typeFilter;
    protected $class;

    protected $batchSize;

    public function __construct(
        ObjectManager $objectManager,
        $paths,
        $class = 'Node',
        $typeFilter = array(),
        $batchSize = 50
    ) {
        $this->objectManager = $objectManager;
        $this->paths = array_values((array) $paths); // ensure paths are indexed numerically
        $this->batchSize = $batchSize;
        $this->typeFilter = $typeFilter;
        $this->class = $class;

        $this->loadBatch();
    }

    /**
     * Return the batchSize
     *
     * @return integer
     */
    public function getBatchSize()
    {
        return $this->batchSize;
    }

    /**
     * Return the type filter
     *
     * @return string
     */
    public function getTypeFilter()
    {
        return $this->typeFilter;
    }

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        return $this->nodes[$this->paths[$this->position]];
    }

    /**
     * {@inheritDoc}
     */
    public function next()
    {
        $this->position++;
    }

    /**
     * {@inheritDoc}
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * {@inheritDoc}
     */
    public function valid()
    {
        if (!isset($this->paths[$this->position])) {
            return false;
        }

        $path = $this->paths[$this->position];

        // skip any paths which have been filtered in userland
        // and move on
        if ($path === null) {
            $this->position++;

            return $this->valid();
        }

        if (!array_key_exists($path, $this->nodes)) {
            $this->loadBatch();
        }

        if (empty($this->nodes[$path])) {
            $this->position++;

            return $this->valid();
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function key()
    {
        return $this->paths[$this->position];
    }

    /**
     * Load a batch of records according to the
     * batch size.
     *
     * @param integer $position - Optional position to start from
     */
    protected function loadBatch($position = null)
    {
        if (0 === count($this->paths)) {
            return;
        }

        $paths = array_slice(
            $this->paths,
            $position ? $position : $this->position,
            $this->batchSize
        );

        $nodes = $this->objectManager->getNodesByPathAsArray(
            $paths, $this->class, $this->typeFilter
        );

        foreach ($paths as $path) {
            $this->nodes[$path] = isset($nodes[$path]) ? $nodes[$path] : null;
        }
    }

    /**
     * Ensure that the given path is loaded from the database.
     * We will iterate over the batches until we either get to
     * the end or we find the node we are looking for.
     *
     * Subsequent calls will start loading from the first path
     * which does not have a corresponding array key in the nodes array
     * - if the node is indeed not already loaded.
     *
     * @param integer $offset
     */
    protected function ensurePathLoaded($offset)
    {
        if (count($this->paths) > 0) {
            if (!array_key_exists($offset, $this->nodes)) {
                // start loading batches from the position of the first
                // "missing" node
                $position = null;
                foreach ($this->paths as $position => $path) {
                    if (!array_key_exists($path, $this->nodes)) {
                        break;
                    }
                }

                while (isset($this->paths[$position])) {
                    // keep loading batches until we get to the end of the paths
                    // or we find the one we want.
                    $this->loadBatch($position);
                    $position += $this->batchSize;
                    if (array_key_exists($offset, $this->nodes)) {
                        break;
                    }
                }
            }
        }

        // if it wasn't found, it doesn't exist, set it to null
        if (!array_key_exists($offset, $this->nodes)) {
            $this->nodes[$offset] = null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset)
    {
        $this->ensurePathLoaded($offset);

        return $this->nodes[$offset] === null ? false : true;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        $this->ensurePathLoaded($offset);

        return $this->nodes[$offset];
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new \InvalidArgumentException('Node path collection is read only.');
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset)
    {
        throw new \InvalidArgumentException('Node path collection is read only.');
    }
}
