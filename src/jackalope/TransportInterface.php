<?php
/**
 * Transport is responsible of talking to the backend.
 * The Transport is told how to access that backend in its constructor.
 */
interface jackalope_TransportInterface {
    //TODO: extract methods from DavexClient
    /**
     * Set this transport to a specific credential and a workspace.
     * This can only be called once. To connect to another workspace or with
     * another credential, use a fresh instance of transport.
     *
     * @param credentials A PHPCR_SimpleCredentials instance (this is the only type currently understood)
     * @param workspaceName The workspace name for this transport.
     * @return true on success (exceptions on failure)
     * @throws PHPCR_LoginException if authentication or authorization (for the specified workspace) fails
     * @throws PHPCR_NoSuchWorkspacexception if the specified workspaceName is not recognized
     * @throws PHPCR_RepositoryException if another error occurs
     */
    public function login(PHPCR_CredentialsInterface $credentials, $workspaceName);

    /**
     * Get the repository descriptors from the jackrabbit server
     * This happens without login or accessing a specific workspace.
     *
     * @return Array with name => Value for the descriptors
     * @throws PHPCR_RepositoryException if error occurs
     */
    public function getRepositoryDescriptors();
}

