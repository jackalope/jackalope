<?php

namespace Jackalope\Transport;

/**
 * Representing a node move operation
 */
class MoveNodeOperation extends Operation
{
    /**
     * The destination path where this node is to be moved to
     *
     * @var string
     */
    public $dstPath;

    public function __construct($srcPath, $dstPath)
    {
        parent::__construct($srcPath);
        $this->dstPath = $dstPath;
    }
}