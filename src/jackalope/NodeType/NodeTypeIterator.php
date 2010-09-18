<?php

namespace jackalope\NodeType;
use jackalope;

/**
 * An iterator for NodeType objects.
 */
class NodeTypeIterator extends RangeIterator implements \PHPCR_NodeType_NodeTypeIteratorInterface {
    
    public function __construct($items) {
        parent::__construct($items);
    }
    
    /**
     * Returns the next NodeType in the iteration.
     *
     * @return PHPCR_NodeTypeInterface the next NodeType in the iteration
     * @throws OutOfBoundsException if iteration has no more NodeTypes
     */
    public function nextNodeType() {
        return $this->next();
    }

}

