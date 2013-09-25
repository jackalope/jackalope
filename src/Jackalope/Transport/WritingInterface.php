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
 * @license http://opensource.org/licenses/MIT MIT License
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
     * @throws \PHPCR\RepositoryException if the name contains invalid characters
     *
     * @see http://www.day.com/specs/jcr/2.0/3_Repository_Model.html#3.2.2%20Local%20Names
     */
    public function assertValidName($name);

    /**
     * Copies a Node from src (potentially from another workspace) to dst in
     * the current workspace.
     *
     * This method does not need to load the node but can execute the copy
     * directly in the storage.
     *
     * If there already is a node at $destAbsPath, the transport may merge
     * nodes as described in the WorkspaceInterface::copy documentation.
     *
     * @param string $srcAbsPath   Absolute source path to the node
     * @param string $destAbsPath  Absolute destination path including the new
     *      node name
     * @param string $srcWorkspace The workspace where the source node can be
     *      found or null for current workspace
     *
     * @link http://www.ietf.org/rfc/rfc2518.txt
     *
     * @see \PHPCR\WorkspaceInterface::copy
     */
    public function copyNode($srcAbsPath, $destAbsPath, $srcWorkspace = null);

    /**
     * Clones the subgraph at the node srcAbsPath in srcWorkspace to the new
     * location at destAbsPath in this workspace.
     *
     * There may be no node at dstAbsPath
     * This method does not need to load the node but can execute the clone
     * directly in the storage.
     *
     * @param string $srcWorkspace The workspace where the source node can be found
     * @param string $srcAbsPath   Absolute source path to the node
     * @param string $destAbsPath  Absolute destination path including the new
     *      node name
     * @param bool $removeExisting whether to remove a node with the same identifier
     *      if there exists one
     *
     * @link http://www.ietf.org/rfc/rfc2518.txt
     *
     * @see \PHPCR\WorkspaceInterface::cloneFrom
     */
    public function cloneFrom($srcWorkspace, $srcAbsPath, $destAbsPath, $removeExisting);

    /**
     * Update a node and its children to match its corresponding node in the specified workspace
     *
     * @param Node   $node         the node to update
     * @param string $srcWorkspace The workspace where the corresponding source node can be found
     */
    public function updateNode(Node $node, $srcWorkspace);

    /**
     * Perform a batch of move operations in the order of the passed array
     *
     * @param \Jackalope\Transport\MoveNodeOperation[] $operations
     */
    public function moveNodes(array $operations);

    /**
     * Moves a node from src to dst outside of a transaction
     *
     * @param string $srcAbsPath Absolute source path to the node
     * @param string $dstAbsPath Absolute destination path (must NOT include
     *      the new node name)
     *
     * @link http://www.ietf.org/rfc/rfc2518.txt
     *
     * @see \Jackalope\Workspace::moveNode
     */
    public function moveNodeImmediately($srcAbsPath, $dstAbsPath);

    /**
     * Reorder the children of $node as the node said it needs them reordered.
     *
     * You can either get the reordering list with getOrderCommands or use
     * getNodeNames to get the absolute order.
     *
     * @param Node $node the node to reorder its children
     */
    public function reorderChildren(Node $node);

    /**
     * Perform a batch remove operation.
     *
     * Take care that cyclic REFERENCE properties of to be deleted nodes do not
     * lead to errors.
     *
     * @param \Jackalope\Transport\RemoveNodeOperation[] $operations
     */
    public function deleteNodes(array $operations);

    /**
     * Perform a batch remove operation.
     *
     * @param \Jackalope\Transport\RemovePropertyOperation[] $operations
     */
    public function deleteProperties(array $operations);

    /**
     * Deletes a node and the whole subtree under it outside of a transaction
     *
     * @param string $path Absolute path to the node
     *
     * @see \Jackalope\Workspace::removeItem
     *
     * @throws \PHPCR\PathNotFoundException if the item is already deleted on
     *      the server. This should not happen if ObjectManager is correctly
     *      checking.
     * @throws \PHPCR\RepositoryException if not logged in or another error occurs
     */
    public function deleteNodeImmediately($path);

    /**
     * Deletes a property outside of a transaction
     *
     * @param string $path Absolute path to the property
     *
     * @see \Jackalope\Workspace::removeItem
     *
     * @throws \PHPCR\PathNotFoundException if the item is already deleted on
     *      the server. This should not happen if ObjectManager is correctly
     *      checking.
     * @throws \PHPCR\RepositoryException if not logged in or another error occurs
     */
    public function deletePropertyImmediately($path);

    /**
     * Store all nodes in the AddNodeOperations
     *
     * Transport stores the node at its path, with all properties (but do not
     * store children).
     *
     * The transport is responsible to ensure that the node is valid and
     * has to generate autocreated properties.
     *
     * Note: Nodes in the log may be deleted if they are deleted. The delete
     * request will be passed later, according to the log. You should still
     * create it here as it might be used temporarily in move operations or
     * such. Use Node::getPropertiesForStoreDeletedNode in that case to avoid
     * a status check of the deleted node.
     *
     * @see BaseTransport::validateNode
     *
     * @param \Jackalope\Transport\AddNodeOperation[] $operations the operations containing the nodes to store
     *
     * @throws \PHPCR\RepositoryException if not logged in or another error occurs
     */
    public function storeNodes(array $operations);

    /**
     * Update the properties of a node
     *
     * @param Node $node the node to update
     */
    public function updateProperties(Node $node);

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
     * @param string $uri    The URI to be mapped.
     */
    public function registerNamespace($prefix, $uri);

    /**
     * Unregister an existing namespace.
     *
     * Validation based on what was returned from getNamespaces has already
     * happened in the NamespaceRegistry.
     *
     * @param string $prefix The prefix to unregister.
     */
    public function unregisterNamespace($prefix);

    /**
     * Called before any data is written.
     */
    public function prepareSave();

    /**
     * Called after everything internally is done in the save() method
     * so the transport has a chance to do final stuff (or commit everything
     * at once).
     */
    public function finishSave();

    /**
     * Called if a save operation caused an exception.
     */
    public function rollbackSave();
}
