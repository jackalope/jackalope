<?php

namespace Jackalope\Transport;

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
     * @param string[] $paths      absolute paths to the nodes
     * @param string[] $typeFilter list of node types to find, with
     *                             semantics as in Node::getNodes meaning a supertype must also match
     *
     * @return array<string, \stdClass> keys are the absolute paths, values the node data decoded from json with associative = true
     *
     * @see TransportInterface::getNodes
     */
    public function getNodesFiltered(array $paths, array $typeFilter): array;

    /**
     * Get the names of child nodes of a node filtered by a type filter.
     *
     * @param string   $parentPath absolute path to the parent node
     * @param string[] $names      The child node names to filter by type
     * @param string[] $typeFilter list of node types to find, with
     *                             semantics as in Node::getNodes meaning a supertype must also match
     *
     * @return string[] list of relative node names at that parent that match the criteria
     *
     * @see Node::getNodeNames
     */
    public function filterChildNodeNamesByType(string $parentPath, array $names, array $typeFilter): array;
}
