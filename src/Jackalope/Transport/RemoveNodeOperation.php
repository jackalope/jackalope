<?php

namespace Jackalope\Transport;
use Jackalope\Node;

/**
 * Representing a node remove operation.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
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
