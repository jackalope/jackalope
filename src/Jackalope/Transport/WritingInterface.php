<?php

namespace Jackalope\Transport;

use Jackalope\Property;
use Jackalope\Node;

/**
 * Defines the methods needed for Writing support
 *
 * Notes:
 *
 * Registering and removing namespaces is also part of this chapter.
 *
 * The announced IDENTIFIER_STABILITY must be guaranteed by the transport.
 * The interface does not differ though.
 *
 * @see <a href="http://www.day.com/specs/jcr/2.0/10_Writing.html">JCR 2.0, chapter 10</a>
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 */
interface WritingInterface extends TransportInterface
{
    /**
     * Whether this node name conforms to the specification
     *
     * Note: There is a minimal implementation in BaseTransport
     *
     * @param string $name The name to check
     *
     * @return boolean always true, if the name is not valid a RepositoryException is thrown
     *
     * @see http://www.day.com/specs/jcr/2.0/3_Repository_Model.html#3.2.2%20Local%20Names
     *
     * @throws \PHPCR\RepositoryException if the name contains invalid characters
     */
    public function assertValidName($name);

    /**
     * Copies a Node from src (potentially from another workspace) to dst in
     * the current workspace.
     *
     * This method does not need to load the node but can execute the copy
     * directly in the storage.
     *
     * @param string $srcAbsPath Absolute source path to the node
     * @param string $dstAbsPath Absolute destination path (must include the
     *      new node name)
     * @param string $srcWorkspace The workspace where the source node can be
     *      found or null for current workspace
     *
     * @return void
     *
     * @link http://www.ietf.org/rfc/rfc2518.txt
     *
     * @see \Jackalope\Workspace::copy
     */
    function copyNode($srcAbsPath, $dstAbsPath, $srcWorkspace = null);

    /**
     * Clones the subgraph at the node srcAbsPath in srcWorkspace to the new
     * location at destAbsPath in this workspace.
     *
     * There may be no node at dstAbsPath
     * This method does not need to load the node but can execute the clone
     * directly in the storage.
     *
     * @param string $srcWorkspace The workspace where the source node can be found
     * @param string $srcAbsPath Absolute source path to the node
     * @param string $destAbsPath Absolute destination path (must include the
     *      new node name)
     * @param bool $removeExisting whether to remove existing nodes at $destAbsPath
     *
     * @return void
     *
     * @link http://www.ietf.org/rfc/rfc2518.txt
     *
     * @see \Jackalope\Workspace::cloneFrom
     */
    function cloneFrom($srcWorkspace, $srcAbsPath, $destAbsPath, $removeExisting);

    /**
     * Moves a node from src to dst
     *
     * @param string $srcAbsPath Absolute source path to the node
     * @param string $dstAbsPath Absolute destination path (must NOT include
     *      the new node name)
     *
     * @return void
     *
     * @link http://www.ietf.org/rfc/rfc2518.txt
     *
     * @see \Jackalope\Workspace::moveNode
     */
    function moveNode($srcAbsPath, $dstAbsPath);

    /**
     * Reorder the children at $path
     *
     * The $reorders is an array with pairs of child node names. The first name
     * must be reordered to the position right before the second name.
     *
     * @param string $absPath absolute path to the parent node
     * @param array $reorders list of reordering pars
     *
     * @return void
     */
    function reorderNodes($absPath, $reorders);

    /**
     * Deletes a node and the whole subtree under it
     *
     * @param string $path Absolute path to the node
     *
     * @return void
     *
     * @throws \PHPCR\PathNotFoundException if the item is already deleted on
     *      the server. This should not happen if ObjectManager is correctly
     *      checking.
     * @throws \PHPCR\RepositoryException if not logged in or another error occurs
     */
    function deleteNode($path);

    /**
     * Deletes a property
     *
     * @param string $path Absolute path to the property
     *
     * @return void
     *
     * @throws \PHPCR\RepositoryException if not logged in or another error occurs
     */
    function deleteProperty($path);

    /**
     * Recursively store a node and its children to the given absolute path.
     *
     * Transport stores the node at its path, with all properties and all
     * children.
     *
     * The transport is responsible to ensure that the node is valid and
     * has to generate autocreated properties.
     *
     * @see BaseTransport::validateNode
     *
     * @param Node $node the node to store
     *
     * @throws \PHPCR\RepositoryException if not logged in or another error occurs
     */
    function storeNode(Node $node);

    /**
     * Stores a property to its absolute path
     *
     * @param Property
     *
     * @return void
     *
     * @throws \PHPCR\RepositoryException if not logged in or another error occurs
     */
    function storeProperty(Property $property);

    /**
     * Register a new namespace.
     *
     * Validation based on what was returned from getNamespaces has already
     * happened in the NamespaceRegistry.
     *
     * The transport is however responsible of removing an existing prefix for
     * that uri, if one exists. As well as removing the current uri mapped to
     * this prefix if this prefix is already existing.
     *
     * @param string $prefix The prefix to be mapped.
     * @param string $uri The URI to be mapped.
     *
     * @return void
     */
    function registerNamespace($prefix, $uri);

    /**
     * Unregister an existing namespace.
     *
     * Validation based on what was returned from getNamespaces has already
     * happened in the NamespaceRegistry.
     *
     * @param string $prefix The prefix to unregister.
     *
     * @return void
     */
    function unregisterNamespace($prefix);

    /**
     * Called before any data is written
     *
     * @return void
     */
    function prepareSave();

    /**
     * Called after everything internally is done in the save() method
     *  so the transport has a chance to do final stuff (or commit everything at once)
     *
     * @return void
     */
    function finishSave();

    /**
     * Called if a save operation caused an exception
     *
     * @return void
     */
    function rollbackSave();
}
