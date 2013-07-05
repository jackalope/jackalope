<?php

namespace Jackalope;

use PHPCR\NodeInterface;
use Jackalope\Transport\NodeTypeFilterInterface;
use Jackalope\Node;

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
    ) 
    {
        $this->objectManager = $objectManager;
        $this->paths = array_values($paths); // ensure paths are indexed numerically
        $this->batchSize = $batchSize;
        $this->typeFilter = $typeFilter;
        $this->class = $class;

        $this->loadBatch();
    }

    public function getBatchSize()
    {
        return $this->batchSize;
    }

    public function getTypeFilter()
    {
        return $this->typeFilter;
    }

    public function current()
    {
        $current = $this->nodes[$this->paths[$this->position]];
        return $current;
    }

    public function next()
    {
        $this->position++;
    }

    public function rewind()
    {
        $this->position = 0;
    }

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

        return true;
    }

    public function key()
    {
        return $this->paths[$this->position];
    }

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

    protected function ensurePathLoaded($offset)
    {
        if (count($this->paths) > 0) {
            if (!array_key_exists($offset, $this->nodes)) {
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

        // if it wasn't found, it doesn't exist.
        if (!isset($this->nodes[$offset])) {
            $this->nodes[$offset] = null;
        }
    }

    public function offsetExists($offset)
    {
        $this->ensurePathLoaded($offset);

        return $this->nodes[$offset] === null ? false : true;
    }

    public function offsetGet($offset)
    {
        $this->ensurePathLoaded($offset);

        return $this->nodes[$offset];
    }

    public function offsetSet($offset, $value)
    {
        throw new \InvalidArgumentException('Node path collection is read only.');
    }

    public function offsetUnset($offset)
    {
        throw new \InvalidArgumentException('Node path collection is read only.');
    }
}
