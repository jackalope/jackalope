<?php
namespace jackalope;

/**
 * Extends Iterator with the skip, getSize and getPosition methods. The base
 * iterator of all type-specific iterators in the JCR and its sub packages.
 */
class RangeIterator implements \PHPCR_RangeIteratorInterface {
    protected $items;
    protected $pos;
    /**
     * @param $items consecutive array of node resp property objects
     */
    public function __construct($items) {
        $this->items = $items;
        $this->pos = 0;
    }

    /**
     * Skip a number of elements in the iterator.
     *
     * @param integer $skipNum the non-negative number of elements to skip
     * @throws OutOfBoundsException if skipped past the last element in the iterator.
     */
    public function skip($skipNum) {
        if ($this->pos + $skipNum >= $this->getSize()) {
            throw new OutOfBoundsException('skipping past end');
        }
        $this->pos += $skipNum;
    }

    /**
     * Returns the total number of of items available through this iterator.
     *
     * For example, for some node $n, $n->getNodes()->getSize() returns the
     * number of child nodes of $n visible through the current Session.
     *
     * In some implementations precise information about the number of elements may
     * not be available. In such cases this method must return -1. API clients will
     * then be able to use RangeIterator->getNumberRemaining() to get an
     * estimate on the number of elements.
     *
     * @return integer
     */
    public function getSize() {
        return count($this->items);
    }

    /**
     * Returns the current position within the iterator. The number
     * returned is the 0-based index of the next element in the iterator,
     * i.e. the one that will be returned on the subsequent next() call.
     *
     * Note that this method does not check if there is a next element,
     * i.e. an empty iterator will always return 0.
     *
     * @return integer
     */
    public function getPosition() {
        return $this->pos;
    }

    /** php has no strong typing, so we can just do this here */
    public function next() {
        if ($this->pos >= $this->getSize()) {
            throw new OutOfBoundsException('skipping past end');
        }
        return $this->items[$this->pos++];
    }

    public function current() {
        if (!isset($this->items[$this->pos])) return null;
        return $this->items[$this->pos];
    }
    public function key() {
        return $this->pos;
    }
    public function valid() {
        return isset($this->items[$this->pos]);
    }
    public function rewind() {
        $this->pos = 0;
    }
}
