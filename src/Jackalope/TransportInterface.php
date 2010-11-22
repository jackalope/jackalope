<?php
/**
 * Definition of the interface to be used for implementing a transport.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 *
 * @package jackalope
 * @subpackage transport
 */
namespace Jackalope;

/**
 * Implementation specific interface:
 * Jackalope encapsulates all communication to the backend within this interface.
 *
 * The Transport is told how to access that backend in its constructor.
 * Look in the transport/ subfolder for actual implementations.
 *
 * TODO: THIS INTERFACE IS INCOMPLETE! get all methods that are really needed from DavexClient
 *
 * @package jackalope
 * @subpackage transport
 */
interface TransportInterface
{
    /**
     * Set this transport to a specific credential and a workspace.
     *
     * This can only be called once. To connect to another workspace or with
     * another credential, use a fresh instance of transport.
     *
     * @param credentials A \PHPCR\SimpleCredentials instance (this is the only type currently understood)
     * @param workspaceName The workspace name for this transport.
     * @return true on success (exceptions on failure)
     *
     * @throws \PHPCR\LoginException if authentication or authorization (for the specified workspace) fails
     * @throws \PHPCR\NoSuchWorkspacexception if the specified workspaceName is not recognized
     * @throws \PHPCR\RepositoryException if another error occurs
     */
    public function login(\PHPCR\CredentialsInterface $credentials, $workspaceName);

    /**
     * Get the repository descriptors from the jackrabbit server
     * This happens without login or accessing a specific workspace.
     *
     * @return Array with name => Value for the descriptors
     * @throws \PHPCR\RepositoryException if error occurs
     */
    public function getRepositoryDescriptors();

    /**
     * Get the registered namespaces mappings from the backend.
     *
     * @return array Associative array of prefix => uri
     *
     * @throws \PHPCR\RepositoryException if now logged in
     */
    public function getNamespaces();
}

