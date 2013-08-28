<?php

namespace Jackalope\Transport;

/**
 * Defines the methods needed for permission checks.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
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
