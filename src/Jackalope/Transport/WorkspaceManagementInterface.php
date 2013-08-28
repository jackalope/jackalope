<?php

namespace Jackalope\Transport;

/**
 * Defines the methods needed for Workspace Management support.
 *
 * @see <a href="http://www.day.com/specs/jcr/2.0/13_Workspace_Management.html">JCR 2.0, chapter 13</a>
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
interface WorkspaceManagementInterface extends TransportInterface
{

    /**
     * Creates a new Workspace with the specified name. The new workspace is
     * empty, meaning it contains only root node.
     *
     * If srcWorkspace is given:
     * Creates a new Workspace with the specified name initialized with a
     * clone of the content of the workspace srcWorkspace. Semantically,
     * this method is equivalent to creating a new workspace and manually
     * cloning srcWorkspace to it; however, this method may assist some
     * implementations in optimizing subsequent Node.update and Node.merge
     * calls between the new workspace and its source.
     *
     * The new workspace can be accessed through a login specifying its name.
     *
     * @param string $name         A String, the name of the new workspace.
     * @param string $srcWorkspace The name of the workspace from which the new
     *      workspace is to be cloned.
     *
     * @throws \PHPCR\AccessDeniedException if the session through which this
     *      Workspace object was acquired does not have sufficient access to
     *      create the new workspace.
     * @throws \PHPCR\UnsupportedRepositoryOperationException if the repository
     *      does not support the creation of workspaces.
     * @throws \PHPCR\NoSuchWorkspaceException if $srcWorkspace does not exist.
     * @throws \PHPCR\RepositoryException      if another error occurs.
     */
    public function createWorkspace($name, $srcWorkspace = null);

    /**
     * Deletes the workspace with the specified name from the repository,
     * deleting all content within it.
     *
     * @param string $name The name of the workspace.
     *
     * @throws \PHPCR\UnsupportedRepositoryOperationException if the repository
     *      does not support the deletion of workspaces.
     * @throws \PHPCR\RepositoryException if another error occurs.
     */
    public function deleteWorkspace($name);
}
