<?php

class jackalope_Repository implements PHPCR_RepositoryInterface {
    protected $transport;
    protected $descriptors;

    /** create repository, either with uri or transport
     * @param $uri Location of the server (ignored if $transport is specified)
     * @param $transport Optional transport implementation. If specified, $uri is ignored
     */
    public function __construct($uri=null, jackalope_TransportInterface $transport=null) {
        if ($transport==null) {
            $transport = new jackalope_transport_DavexClient($uri);
        }
        $this->descriptors = $transport->getRepositoryDescriptors();
    }

    /**
    * Authenticates the user using the supplied credentials. If workspaceName is recognized as the
    * name of an existing workspace in the repository and authorization to access that workspace
    * is granted, then a new Session object is returned. The format of the string workspaceName
    * depends upon the implementation.
    * If credentials is null, it is assumed that authentication is handled by a mechanism external
    * to the repository itself and that the repository implementation exists within a context
    * (for example, an application server) that allows it to handle authorization of the request
    * for access to the specified workspace.
    *
    * If workspaceName is null, a default workspace is automatically selected by the repository
    * implementation. This may, for example, be the "home workspace" of the user whose credentials
    * were passed, though this is entirely up to the configuration and implementation of the
    * repository. Alternatively, it may be a "null workspace" that serves only to provide the
    * method Workspace.getAccessibleWorkspaceNames(), allowing the client to select from among
    * available "real" workspaces.
    *
    * Note: The Java API defines this method with multiple differing signatures.
    *
    * @param PHPCR_CredentialsInterface $credentials The credentials of the user
    * @param string $workspaceName the name of a workspace
    * @return PHPCR_SessionInterface a valid session for the user to access the repository
    * @throws PHPCR_LoginException if authentication or authorization (for the specified workspace) fails
    * @throws PHPCR_NoSuchWorkspacexception if the specified workspaceName is not recognized
    * @throws PHPCR_RepositoryException if another error occurs
    * @api
    */
    public function login($credentials = NULL, $workspaceName = NULL) {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns a string array holding all descriptor keys available for this
     * implementation, both the standard descriptors defined by the string
     * constants in this interface and any implementation-specific descriptors.
     * Used in conjunction with getDescriptorValue($key) and getDescriptorValues($key)
     * to query information about this repository implementation.
     *
     * @return array a string array holding all descriptor keys
     * @api
     */
    public function getDescriptorKeys() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns TRUE if $key is a standard descriptor
     * defined by the string constants in this interface and FALSE if it is
     * either a valid implementation-specific key or not a valid key.
     *
     * @param string $key a descriptor key.
     * @return boolan whether $key is a standard descriptor.
     * @api
     */
    public function isStandardDescriptor($key) {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns TRUE if $key is a valid single-value descriptor;
     * otherwise returns FALSE.
     *
     * @param string $key a descriptor key.
     * @return boolean whether the specified descriptor is multi-valued.
     * @api
     */
    public function isSingleValueDescriptor($key) {
        throw new jackalope_NotImplementedException();
    }

    /**
     * The value of a single-value descriptor is found by
     * passing the key for that descriptor to this method.
     * If $key is the key of a multi-value descriptor
     * or not a valid key this method returns NULL.
     *
     * @param string $key a descriptor key.
     * @return PHPCR_ValueInterface The value of the indicated descriptor
     * @api
     */
    public function getDescriptorValue($key) {
        throw new jackalope_NotImplementedException();
    }

    /**
     * The value array of a multi-value descriptor is found by
     * passing the key for that descriptor to this method.
     * If $key is the key of a single-value descriptor
     * then this method returns that value as an array of size one.
     * If $key is not a valid key this method returns NULL.
     *
     * @param string $key a descriptor key.
     * @return array of PHPCR_ValueInterface the value array for the indicated descriptor
     * @api
     */
    public function getDescriptorValues($key) {
        throw new jackalope_NotImplementedException();
    }

    /**
     * A convenience method. The call
     *  String s = repository.getDescriptor(key);
     * is equivalent to
     *  Value v = repository.getDescriptor(key);
     *  String s = (v == null) ? null : v.getString();
     *
     * @param string $key a descriptor key.
     * @return a descriptor value in string form.
     * @api
     */
    public function getDescriptor($key) {
        throw new jackalope_NotImplementedException();
    }

}
