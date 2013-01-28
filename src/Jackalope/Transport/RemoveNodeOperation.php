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

    /**
     * Whether this remove operations was later determined to be skipped
     * (i.e. a parent node is removed as well.)
     *
     * @var bool
     */
    public $skip = false;

    public function __construct($srcPath, NodeInterface $node)
    {
        parent::__construct($srcPath);
        $this->node = $node;
    }
}