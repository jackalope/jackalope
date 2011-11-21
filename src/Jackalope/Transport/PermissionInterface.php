<?php

namespace Jackalope\Transport;

use Jackalope\NodeType\NodeTypeManager;

/**
 * Implementation specific interface for implementing transactional transport
 * layers.
 *
 * Jackalope encapsulates all communication with the storage backend within
 * this interface.
 *
 * Adds the methods necessary for authentication handling
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 */
interface PermissionInterface extends TransportInterface
{
    /**
     * Return the permissions of the current session on the node given by path.
     *
     * The result of this function is an array of zero, one or more strings
     * from add_node, read, remove, set_property.
     *
     * @param string $path the path to the node we want to check
     *
     * @return array of string
     */
    public function getPermissions($path);
}
