<?php

namespace Jackalope\Transport;

/**
 * Base for all operations
 */
abstract class Operation
{
    /**
     * The source path is the path of the node this operation applies to
     *
     * @var string
     */
    public $srcPath;

    public function __construct($srcPath)
    {
        $this->srcPath = $srcPath;
    }
}