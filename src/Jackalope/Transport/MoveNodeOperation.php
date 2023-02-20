<?php

namespace Jackalope\Transport;

/**
 * Representing a node move operation.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
class MoveNodeOperation extends Operation
{
    /**
     * The destination path where this node is to be moved to.
     */
    public string $dstPath;

    public function __construct(string $srcPath, string $dstPath)
    {
        parent::__construct($srcPath, self::MOVE_NODE);
        $this->dstPath = $dstPath;
    }
}
