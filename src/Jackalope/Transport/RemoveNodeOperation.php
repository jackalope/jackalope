<?php

namespace Jackalope\Transport;
use PHPCR\NodeInterface;

/**
 * Representing a node remove operation
 */
class RemoveNodeOperation extends Operation
{
    /**
     * The item to remove
     *
     * @var NodeInterface
     */
    public $node;

    public function __construct($srcPath, NodeInterface $node)
    {
        parent::__construct($srcPath, self::REMOVE_NODE);
        $this->node = $node;
    }
}