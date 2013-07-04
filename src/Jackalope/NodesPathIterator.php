<?php

namespace Jackalope;

use Jackalope\Transport\TransportInterface;
use PHPCR\NodeInterface;
use Jackalope\Transport\NodeTypeFilterInterface;

class NodesPathIterator implements \SeekableIterator // , \Countable, \ArrayAccess 
{
    protected $position;
    protected $nodes;
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

        if (!isset($this->nodes[$path])) {
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
            $this->position + $this->batchSize
        );

        $nodes = $this->objectManager->getNodesByPathAsArray($paths);

        foreach ($paths as $path) {
            $this->nodes[$path] = isset($nodes[$path]) ? $nodes[$path] : null;
        }
    }
}
