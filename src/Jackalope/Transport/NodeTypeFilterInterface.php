<?php

namespace Jackalope\Transport;

use PHPCR\RepositoryException;

/**
 * Defines the methods needed for getting nodes while filtering for type.
 *
 * If a transport can not do this efficiently, it should not implement this
 * interface and Jackalope will use the normal getNodes and filter itself on
 * the result.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
interface NodeTypeFilterInterface extends TransportInterface
{
    /**
     * Get the nodes from an array of absolute paths, filtered by a
     * list of node type names of which the nodes must be to be included in
     * the result.
     *
     * @param array        $path       Absolute paths to the nodes.
     * @param array|string $typeFilter List of node types to find, with
     *      semantics as in Node::getNodes meaning a supertype must also match.
     *
     * @return array keys are the absolute paths, values is the node data as
     *      associative array (decoded from json with associative = true)
     *
     * @throws \PHPCR\RepositoryException if not logged in
     *
     * @see TransportInterface::getNodes
     */
    public function getNodesFiltered($paths, $typeFilter);

    /**
     * Get the names of child nodes of a node filtered by a type filter.
     *
     * @param array        $parentPath Absolute path to the parent node.
     * @param array        $names      The child node names to filter by type
     * @param array|string $typeFilter List of node types to find, with
     *      semantics as in Node::getNodes meaning a supertype must also match.
     *
     * @return array list of relative node names at that parent that match the criteria
     *
     * @throws \PHPCR\RepositoryException if not logged in
     *
     * @see Node::getNodeNames
     */
    public function filterChildNodeNamesByType($parentPath, $names, $typeFilter);
}
