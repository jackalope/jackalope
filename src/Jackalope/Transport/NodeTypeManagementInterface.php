<?php

namespace Jackalope\Transport;

/**
 * Defines the methods needed for Node Type Management support with
 * NodeTypeDefinition instances.
 *
 * There is an alternate interface if your transport implements direct support
 * for the "compact node type and namespace" definition. But if it does not,
 * Jackalope will parse the cnd for you and call registerNodeTypes.
 *
 * Note that this is about creating custom node types. The basic node type
 * discovery is part of the CoreInterface.
 *
 * @see <a href="http://www.day.com/specs/jcr/2.0/13_Workspace_Management.html">JCR 2.0, chapter 13</a>
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
interface NodeTypeManagementInterface extends TransportInterface
{
    /**
     * Register a list of node types with the storage backend
     *
     * @param array $types a list of
     *      \PHPCR\NodeType\NodeTypeDefinitionInterface objects
     * @param boolean $allowUpdate whether to fail if node already exists or to
     *      update it
     *
     * @return bool true on success
     *
     * @throws \PHPCR\NodeType\InvalidNodeTypeDefinitionException if the
     *      NodeTypeDefinitionInterface is invalid.
     * @throws \PHPCR\NodeType\NodeTypeExistsException if allowUpdate is false
     *      and the NodeTypeDefinition specifies a node type name that is
     *      already registered.
     * @throws \PHPCR\RepositoryException if another error occurs.
     */
    public function registerNodeTypes($types, $allowUpdate);
}
