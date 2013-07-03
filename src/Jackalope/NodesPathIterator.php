<?php

namespace Jackalope;

use Jackalope\Transport\TransportInterface;
use PHPCR\NodeInterface;
use Jackalope\Transport\NodeTypeFilterInterface;

class NodesPathIterator implements \SeekableIterator // , \Countable, \ArrayAccess 
{
    protected $position;
    protected $nodes;

    /**
     * Fetch paths must be $absPath => $fetchPath
     * as given by ObjectManager
     */
    protected $fetchPaths;

    protected $fetchPathIndex;
    protected $typeFilter;
    protected $class;

    protected $batchSize;

    public function __construct(
        ObjectManager $objectManager, 
        TransportInterface $transport,
        $fetchPaths, 
        $class = 'Node',
        $typeFilter = array(),
        $batchSize = 50
    ) 
    {
        $this->objectManager = $objectManager;
        $this->transport = $transport;

        foreach ($fetchPaths as $absPath => $fetchPath) {
            $this->fetchPathIndex[] = $fetchPath;
        }

        $this->fetchPaths = $fetchPaths;
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
        $current = $this->nodes[$this->fetchPathIndex[$this->position]];
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
        if (!isset($this->fetchPathIndex[$this->position])) {
            return false;
        }

        $path = $this->fetchPathIndex[$this->position];

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
        return $this->fetchPathIndex[$this->position];
    }

    protected function loadBatch()
    {
        $fetchPaths = array_slice(
            $this->fetchPaths,
            $this->position, 
            $this->position + $this->batchSize
        );

        $userlandTypeFilter = false;

        if (null !== $this->typeFilter) {
            if ($this->transport instanceof NodeTypeFilterInterface) {
                $data = $this->transport->getNodesFiltered($fetchPaths, $this->typeFilter);

                foreach ($this->fetchPathIndex as $i => $refFetchPath) {
                    if (!array_key_exists($refFetchPath, $data)) {
                        unset($this->fetchPathIndex[$i]);
                    }
                }
            } else {
                $data = $this->transport->getNodes($fetchPaths);
                $userlandTypeFilter = true;
            }
        } else {
            $data = $this->transport->getNodes($fetchPaths);
        }

        foreach ($fetchPaths as $absPath => $fetchPath) {
            if (array_key_exists($fetchPath, $data)) {
                $node = $this->objectManager->getNodeByPath(
                    $absPath, $this->class, $data[$fetchPath]
                );

                if ($userlandTypeFilter) {
                    if (!$this->matchNodeType($node, (array) $this->typeFilter)) {
                        foreach ($this->fetchPathIndex as $i => $refFetchPath) {
                            if ($fetchPath == $refFetchPath) {
                                unset($this->fetchPathIndex[$i]);
                            }
                        }
                        continue;
                    }
                }

                $this->nodes[$absPath] = $node;
            }
        }
    }

    /**
     * Check if a node is of any of the types listed in typeFilter.
     *
     * @param NodeInterface $node
     * @param array         $typeFilter
     *
     * @return bool
     */
    private function matchNodeType(NodeInterface $node, array $typeFilter)
    {
        foreach ($typeFilter as $type) {
            if ($node->isNodeType($type)) {
                return true;
            }
        }

        return false;
    }
}
