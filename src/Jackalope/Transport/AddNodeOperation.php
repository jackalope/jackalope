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

    public function __construct($srcPath, NodeInterface $node)
    {
        parent::__construct($srcPath, self::ADD_NODE);
        $this->node = $node;
    }
}