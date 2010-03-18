<?php
/**
 * Allows easy iteration through a list of Nodes with nextNode as well as a skip method
 * inherited from RangeIterator.
 */
class jackalope_NodeIterator extends jackalope_RangeIterator implements PHPCR_NodeIteratorInterface {
    /**
     * Returns the next Node in the iteration.
     *
     * @return PHPCR_NodeInterface
     * @throws OutOfBoundsException if the iterator contains no more elements.
     */
    public function nextNode() {
        return $this->next();
    }
}
