<?php

namespace Jackalope;

use PHPCR\NodeInterface;

/**
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
final class NodePathIterator implements \SeekableIterator, \ArrayAccess, \Countable
{
    private ObjectManager $objectManager;
    private int $offset = 0;
    private array $nodes = [];
    private array $paths;
    private $typeFilter;
    private string $class;
    private int $count = 0;
    private int $batchSize;

    /**
     * @param array|\Iterator $paths
     */
    public function __construct(
        ObjectManager $objectManager,
        $paths,
        string $class = Node::class,
        $typeFilter = [],
        int $batchSize = 50
    ) {
        $this->objectManager = $objectManager;
        $this->paths = array_values((array) $paths); // ensure paths are indexed numerically
        $this->batchSize = $batchSize;
        $this->typeFilter = $typeFilter;
        $this->class = $class;

        $this->loadBatch();
    }

    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    /**
     * Return the type filter.
     *
     * @return string
     */
    public function getTypeFilter()
    {
        return $this->typeFilter;
    }

    public function current(): ?NodeInterface
    {
        return $this->nodes[$this->paths[$this->offset]];
    }

    public function next(): void
    {
        ++$this->offset;
    }

    public function rewind(): void
    {
        $this->offset = 0;
    }

    public function valid(): bool
    {
        if (!isset($this->paths[$this->offset])) {
            return false;
        }

        $path = $this->paths[$this->offset];

        // skip any paths which have been filtered in userland
        // and move on
        if (null === $path) {
            ++$this->offset;

            return $this->valid();
        }

        if (!array_key_exists($path, $this->nodes)) {
            $this->loadBatch();
        }

        if (empty($this->nodes[$path])) {
            ++$this->offset;

            return $this->valid();
        }

        return true;
    }

    public function key(): ?string
    {
        return $this->paths[$this->offset];
    }

    /**
     * Load a batch of records according to the
     * batch size.
     *
     * @param int|null $position - Optional position to start from
     */
    private function loadBatch(?int $position = null): void
    {
        if (0 === count($this->paths)) {
            return;
        }

        $paths = array_slice(
            $this->paths,
            $position ?: $this->offset,
            $this->batchSize
        );

        $nodes = $this->objectManager->getNodesByPathAsArray(
            $paths,
            $this->class,
            $this->typeFilter
        );

        foreach ($paths as $path) {
            if (isset($nodes[$path]) && '' !== $nodes[$path]) {
                $this->nodes[$path] = $nodes[$path];
                ++$this->count;
            } else {
                $this->nodes[$path] = null;
            }
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
     */
    private function ensurePathLoaded($offset): void
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

    public function offsetExists($offset): bool
    {
        $this->ensurePathLoaded($offset);

        return null === $this->nodes[$offset] ? false : true;
    }

    public function offsetGet($offset): ?NodeInterface
    {
        $this->ensurePathLoaded($offset);

        return $this->nodes[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        throw new \InvalidArgumentException('Node path collection is read only');
    }

    public function offsetUnset($offset): void
    {
        throw new \InvalidArgumentException('Node path collection is read only');
    }

    public function seek($offset): void
    {
        $this->offset = $offset;
    }

    public function count(): int
    {
        $this->ensurePathLoaded(count($this->paths));

        return $this->count;
    }
}
