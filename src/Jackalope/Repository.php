<?php
namespace Jackalope;

/**
 * The entry point into the content repository. The Repository object is
 * usually acquired through the RepositoryFactory.
 *
 */
class Repository implements \PHPCR\RepositoryInterface
{
    protected $transport;
    /** Array of descriptors. Each is either a string or an array of strings. */
    protected $descriptors;

    /**
     * Create repository, either with uri or transport
     * Typical uri for a local jackrabbit server is http://localhost:8080/server
     *
     * @param $uri Location of the server (ignored if $transport is specified)
     * @param $transport Optional transport implementation. If specified, $uri is ignored
     */
    public function __construct($uri = null, TransportInterface $transport = null)
    {
        if ($transport == null) {
            if ($uri === null) {
                throw new \PHPCR\RepositoryException('You have to pass either a uri or a transport argument');
            }
            if ('/' !== substr($uri, -1, 1)) {
                $uri .= '/';
            }
            $transport = Factory::get('Transport\Davex\Client', array($uri)); //default if not specified
        }
        $this->transport = $transport;
    }

    /**
    * Authenticates the user using the supplied credentials. If workspaceName is recognized as the
    * name of an existing workspace in the repository and authorization to access that workspace
    * is granted, then a new Session object is returned. workspaceName is a single string token.
    *
    * null credentials are currently not supported
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
    * @param \PHPCR\CredentialsInterface $credentials The credentials of the user
    * @param string $workspaceName the name of a workspace
    * @return \PHPCR\SessionInterface a valid session for the user to access the repository
    * @throws \PHPCR\LoginException if authentication or authorization (for the specified workspace) fails
    * @throws \PHPCR\NoSuchWorkspacexception if the specified workspaceName is not recognized
    * @throws \PHPCR\RepositoryException if another error occurs
    * @api
    */
    public function login($credentials = NULL, $workspaceName = NULL)
    {
        if ($workspaceName == null) $workspaceName = 'default'; //TODO: can default workspace have other name?
        if (! $this->transport->login($credentials, $workspaceName)) {
            throw new \PHPCR\RepositoryException('transport failed to login without telling why');
        }
        $session = Factory::get('Session', array($this, $workspaceName, $credentials, $this->transport));
        return $session;
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
    public function getDescriptorKeys()
    {
        if (null === $this->descriptors) {
            $this->loadDescriptors();
        }
        return array_keys($this->descriptors);
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
    public function isStandardDescriptor($key)
    {
        $ref = new ReflectionClass('\PHPCR\RepositoryInterface');
        $consts = $ref->getConstantcs();
        return in_array($key, $consts);
    }

    /**
     * Get the string value(s) for this key.
     *
     * @param string $key a descriptor key.
     * @return mixed a descriptor value in string form or an array of strings for multivalue descriptors
     * @api
     */
    public function getDescriptor($key)
    {
        if (null === $this->descriptors) {
            $this->loadDescriptors();
        }
        return (isset($this->descriptors[$key])) ?  $this->descriptors[$key] : null;
        //TODO: is this the proper behaviour? Or what should happen on inexisting key?
    }

    protected function loadDescriptors()
    {
        $this->descriptors = $this->transport->getRepositoryDescriptors();
    }
}
