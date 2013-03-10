<?php

namespace Jackalope\Transport;
use Jackalope\Node;

/**
 * Representing a node remove operation
 */
class RemoveNodeOperation extends Operation
{
    /**
     * The node to remove
     *
     * @var Node
     */
    public $node;

    public function __construct($srcPath, Node $node)
    {
        parent::__construct($srcPath, self::REMOVE_NODE);
        $this->node = $node;
    }
}