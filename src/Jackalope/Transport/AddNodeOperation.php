<?php

namespace Jackalope\Transport;
use PHPCR\NodeInterface;

/**
 * Representing a node add operation
 *
 * Note that the srcPath might differ from the current path the node has (if
 * the node is moved later in the transaction. It still must be added at the
 * original location.
 */
class AddNodeOperation extends Operation
{
    /**
     * The node to add
     *
     * @var NodeInterface
     */
    public $node;

    /**
     * Whether this add operations was later determined to be skipped
     * (i.e. the node was removed again)
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