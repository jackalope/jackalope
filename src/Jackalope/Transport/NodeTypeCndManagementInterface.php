<?php

namespace Jackalope\Transport;

/**
 * Alternate interface for transports that implement direct support for the
 * "compact node type and namespace" definition.
 *
 * If you only implement this interface, Jackalope will convert
 * NodeTypeDefinitions into the cnd format for you.

 * Note that this is about creating custom node types. The basic node type
 * discovery is part of the CoreInterface.
 *
 * @see <a href="http://www.day.com/specs/jcr/2.0/13_Workspace_Management.html">JCR 2.0, chapter 13</a>
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
interface NodeTypeCndManagementInterface extends TransportInterface
{

    /**
     * Register namespaces and new node types or update node types based on a
     * jackrabbit cnd string
     *
     * @param string  $cnd         The cnd definition as string
     * @param boolean $allowUpdate whether to fail if node already exists or to update it
     *
     * @return bool true on success
     *
     * @see \Jackalope\NodeTypeManager::registerNodeTypesCnd
     */
    public function registerNodeTypesCnd($cnd, $allowUpdate);
}
