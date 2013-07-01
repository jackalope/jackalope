<?php

use Jackalope\Transport\TransportInterface;
use Doctrine\Common\Persistence\ObjectManager;

class NodesPathIterator implements \SeekableIterator
{
    protected $paths;
    protected $position;
    protected $nodes;
    protected $indexes;
    protected $typeFilter;

    protected $batchSize = 50;

    public function __construct(
        ObjectManager $objectManager, 
        TransportInterface $transport,
        $paths, 
        $typeFilter = null
    ) {
        $this->objectManager = $objectManager;
        $this->transport = $transport;

        foreach ($paths as $fetchPath => $absPath) {
            $this->indexes[] = $absPath;
        }
    }

    public function current()
    {
        $current = $this->nodes[$this->indexes[$this->position]];
        return $current;
    }

    public function next()
    {
        $next = $this->nodes[$this->indexes[$this->position + 1]];
        if (null === $next) {
            $this->loadBatch();
        } else {
            $this->position++;
        }
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function valid()
    {
        return isset($this->indexes[$this->position]);
    }

    public function key()
    {
        return $this->indexes[$this->position];
    }

    protected function loadBatch()
    {
        $paths = array_slice(
            $this->index, 
            $this->position, 
            $this->position + $this->batchSize
        );

        $userlandTypeFilter = false;

        if ($this->typeFilter) {

            if ($this->transport instanceof NodeTypeFilterInterface) {
                $data = $this->transport->getNodesFiltered($paths, $this->typeFilter);
            } else {
                $data = $this->transport->getNodes($paths);
                $userlandTypeFilter = true;
            }

        } else {
            $data = $this->transport->getNodes($paths);
        }

        foreach ($data as $fetchPath => $datum) {
            if ($userlandTypeFilter
                && !$this->matchNodeType($datum, $typeFilter)
            ) {
                unset($data[$fetchPath]);
            }
        }

        foreach ($paths as $absPath => $fetchPath) {
            if (array_key_exists($fetchPath, $data)) {
                $this->nodes[$absPath] = $this->getNodeByPath($absPath, $class, $data[$fetchPath]);
            } else {
                unset($nodes[$absPath]);
            }
        }
    }
}
