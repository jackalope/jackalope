<?php

namespace Jackalope;

use PHPCR\NodeInterface;
use Jackalope\Transport\NodeTypeFilterInterface;
use Jackalope\Node;

class NodePathIterator implements \SeekableIterator, \ArrayAccess
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

    public function seek($position)
    {
        $this->position = $position;
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

    protected function loadBatch()
    {
        $paths = array_slice(
            $this->paths,
            $this->position, 
            $this->batchSize
        );

        $nodes = $this->objectManager->getNodesByPathAsArray(
            $paths, $this->class, $this->typeFilter
        );

        foreach ($paths as $path) {
            $this->nodes[$path] = isset($nodes[$path]) ? $nodes[$path] : null;
        }
    }

    protected function loadSingle($path)
    {
        $paths = array($path);
        $nodes = $this->objectManager->getNodesByPath($paths, $this->class, $this->typeFilter);
        $node = current($nodes);
        $this->nodes[$path] = $node ? $node : null;
    }

    protected function ensurePathLoaded($path)
    {
        if (in_array($path, $this->paths)) {
            if (!array_key_exists($path, $this->nodes)) {
                $this->loadSingle($path);
            }
        } else {
            $this->nodes[$path] = null;
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
