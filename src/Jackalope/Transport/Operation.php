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
    public const ADD_NODE = 'add-node';

    public const MOVE_NODE = 'move-node';

    public const REMOVE_NODE = 'remove-node';

    public const REMOVE_PROPERTY = 'remove-property';

    /**
     * One of the type constants to know what kind of operation this is.
     */
    public string $type;

    /**
     * The source path is the path of the node this operation applies to.
     */
    public string $srcPath;

    /**
     * Whether this operation was later determined to be skipped
     * (i.e. a parent node is removed as well.).
     */
    public bool $skip = false;

    /**
     * @param string $srcPath source path this operation applies to
     * @param string $type    one of the Operation constants
     */
    public function __construct(string $srcPath, string $type)
    {
        $this->srcPath = $srcPath;
        $this->type = $type;
    }
}
