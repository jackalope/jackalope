<?php
namespace jackalope;

/**
 * Allows easy iteration through a list of Properties with nextProperty as well as a skip method
 * inherited from RangeIterator.
 */
class PropertyIterator extends RangeIterator implements \PHPCR_PropertyIteratorInterface {
    /**
     * Returns the next Property in the iteration.
     *
     * @return PHPCR_PropertyInterface
     * @throws OutOfBoundsException if the iterator contains no more elements.
     */
    public function nextProperty() {
        return $this->next();
    }
}
