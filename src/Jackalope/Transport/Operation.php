<?php

namespace Jackalope\Transport;

/**
 * Base class for all operations buffered in the object manager.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
abstract class Operation
{
    const ADD_NODE = 'add-node';
    const MOVE_NODE = 'move-node';
    const REMOVE_NODE = 'remove-node';
    const REMOVE_PROPERTY = 'remove-property';

    /**
     * One of the type constants to know what kind of operation this is
     *
     * @var string
     */
    public $type;

    /**
     * The source path is the path of the node this operation applies to
     *
     * @var string
     */
    public $srcPath;

    /**
     * Whether this operation was later determined to be skipped
     * (i.e. a parent node is removed as well.)
     *
     * @var bool
     */
    public $skip = false;

    /**
     * @param string $srcPath source path this operation applies to
     * @param string $type    one of the Operation constants
     */
    public function __construct($srcPath, $type)
    {
        $this->srcPath = $srcPath;
        $this->type = $type;
    }
}
