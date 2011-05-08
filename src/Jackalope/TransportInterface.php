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

use Jackalope\NodeType\NodeTypeManager;

/**
 * Implementation specific interface:
 * Jackalope encapsulates all communication with the storage backend within
 * this interface.
 *
 * The Transport is told how to access that backend in its constructor.
 * Look in the transport/ subfolder for actual implementations.
 *
 * This interface is now synchronized with what we had for davex as per 2011-04-13
 * TODO: keep this in sync with Transport/Davex/Client.php
 *
 * @package jackalope
 * @subpackage transport
 */
interface TransportInterface
{
    /**
     * Pass the node type manager into the transport to be used for validation and such.
     *
     * @param NodeTypeManager $nodeTypeManager
     * @return void
     */
    public function setNodeTypeManager(NodeTypeManager $nodeTypeManager);

    /**
     * Get the repository descriptors from the jackrabbit server
     *
     * This happens without login or accessing a specific workspace.
     * With this, you can get some information without being logged in
     *
     * At least, this must return the constants defined in
     * \PHPCR\RepositoryInterface . Doc about each constant is found there.
     * Implementations can add their own constants.
     *
     * @see \PHPCR\RepositoryInterface
     * @return Array with name => value/array of value for the descriptors
     * @throws \PHPCR\RepositoryException if error occurs
     */
    public function getRepositoryDescriptors();

    /**
     * Returns the workspace names that can be used when logging in.
     *
     * @return array List of workspaces that can be specified on login
     */
    public function getAccessibleWorkspaceNames();

    /**
     * Set this transport to a specific credential and a workspace.
     *
     * This can only be called once. To connect to another workspace or with
     * another credential, use a fresh instance of transport.
     *
     * What implementation of credentials is supported is transport specific.
     *
     * @param \PHPCR\CredentialsInterface the credentials to connect with the backend
     * @param workspaceName The workspace name to connect to.
     * @return true on success (exceptions on failure)
     *
     * @throws \PHPCR\LoginException if authentication or authorization (for the specified workspace) fails
     * @throws \PHPCR\NoSuchWorkspacexception if the specified workspaceName is not recognized
     * @throws \PHPCR\RepositoryException if another error occurs
     */
    public function login(\PHPCR\CredentialsInterface $credentials, $workspaceName);


    /***********************************************************************
     * all methods from here below require that login is called first. the *
     * behaviour of transport is undefined if this is not respected.       *
     ***********************************************************************/


    /*****************************
     * Methods for read support *
     *****************************/

    /**
     * Get the registered namespaces mappings from the backend.
     *
     * @return array Associative array of prefix => uri
     *
     * @throws \PHPCR\RepositoryException if not logged in
     */
    public function getNamespaces();


    /**
     * Get the node that is stored at an absolute path
     *
     * TODO: should we call this getNode? does not work for property. (see ObjectManager::getPropertyByPath for more on properties)
     * TODO: does it make sense to have json here or should transport instantiate the node objects?
     *
     * @param string $path Absolute path to identify a special item.
     * @return array for the node (decoded from json)
     *
     * @throws \PHPCR\RepositoryException if not logged in
     */
    public function getItem($path);

    /**
     * Get the node path from a JCR uuid
     *
     * @param string $uuid the id in JCR format
     * @return string Absolute path to the node
     *
     * @throws \PHPCR\ItemNotFoundException if the backend does not know the uuid
     * @throws \PHPCR\RepositoryException if not logged in
     */
    public function getNodePathForIdentifier($uuid);

    /**
     * Retrieves a binary value
     *
     * @param $path
     * @return string with binary data
     */
    public function getBinaryProperty($path); //OPTIMIZE: use the binary interface to only load if really requested?



    /*****************************
     * Methods for write support *
     *****************************/

    /**
     * Copies a Node from src (potentially from another workspace) to dst in
     * the current workspace.
     *
     * This method does not need to load the node but can execute the copy
     * directly in the storage.
     *
     * @param   string  $srcAbsPath     Absolute source path to the node
     * @param   string  $dstAbsPath     Absolute destination path (must include the new node name)
     * @param   string  $srcWorkspace   The workspace where the source node can be found or NULL for current workspace
     * @return void
     *
     * @link http://www.ietf.org/rfc/rfc2518.txt
     * @see \Jackalope\Workspace::copy
     */
    public function copyNode($srcAbsPath, $dstAbsPath, $srcWorkspace = null);

    /**
     * Moves a node from src to dst
     *
     * @param   string  $srcAbsPath     Absolute source path to the node
     * @param   string  $dstAbsPath     Absolute destination path (must NOT include the new node name)
     * @return void
     *
     * @link http://www.ietf.org/rfc/rfc2518.txt
     */
    public function moveNode($srcAbsPath, $dstAbsPath);

    /**
     * Deletes a node and the whole subtree under it
     *
     * @param string $path Absolute path to the node
     * @return bool true on success
     *
     * @throws \PHPCR\RepositoryException if not logged in
     */
    public function deleteNode($path);

    /**
     * Deletes a property
     *
     * @param string $path Absolute path to the property
     * @return bool true on success
     *
     * @throws \PHPCR\RepositoryException if not logged in
     */
    public function deleteProperty($path);

    /**
     * Recursively store a node and its children to the given absolute path.
     *
     * The basename of the path is the name of the node
     *
     * @param string $path Absolute path to the node, name is part after last /
     * @param \PHPCR\NodeType\NodeTypeInterface $primaryType FIXME: would we need this?
     * @param \Traversable $properties array of \PHPCR\PropertyInterface objects
     * @param \Traversable $children array of \PHPCR\NodeInterface objects
     * @return bool true on success
     *
     * @throws \PHPCR\RepositoryException if not logged in
     */
    public function storeNode($path, $properties, $children);

    /**
     * Stores a property to the given absolute path
     *
     * @param string $path Absolute path to store the property to
     * @param \PHPCR\PropertyInterface
     * @return bool true on success
     *
     * @throws \PHPCR\RepositoryException if not logged in
     * TODO: handle not found from backend if containing node was not saved? would be consistency error in ObjectManager
     */
    public function storeProperty($path, \PHPCR\PropertyInterface $property);


    //TODO: set namespace, ...

    /*********************************
     * Methods for NodeType support. *
     *********************************/
    /**
     * Get node types, either filtered or all
     *
     * @param array string names of node types to fetch, if empty array all node types are retrieved
     * @return dom with the definitions (see NodeTypeDefinition::fromXml for what is expected)
     * @throws \PHPCR\RepositoryException if not logged in
     *
     * @see NodeTypeDefinition::fromXml TODO: should transport parse the xml to make this less backend specific?
     */
    public function getNodeTypes($nodeTypes = array());

    /**
     * Register namespaces and new node types or update node types based on a
     * jackrabbit cnd string
     *
     * TODO: provide a php parser in case the storage backend does not know cnd
     *
     * @see \Jackalope\NodeTypeManager::registerNodeTypesCnd
     *
     * @param $cnd The cnd string
     * @param boolean $allowUpdate whether to fail if node already exists or to update it
     * @return bool true on success
     */
    public function registerNodeTypesCnd($cnd, $allowUpdate);

    /**
     * Register a list of node types with the storage backend
     *
     * @param array $types a list of \PHPCR\NodeType\NodeTypeDefinitionInterface objects
     * @param boolean $allowUpdate whether to fail if node already exists or to update it
     * @return bool true on success
     */
    public function registerNodeTypes($types, $allowUpdate);


    /************************************************************************
     * Methods for Search support.                                          *
     * Implement with UnsupportedRepositoryOperationException if you do not *
     * handle search                                                        *
     ************************************************************************/

    /**
     * Search something with the backend.
     *
     * @param string $query a jcr-sql2 query
     * @param int $limit number of results to return, defaults to unlimited
     * @param int $offset index to start with results, defaults to the first result
     * @return xml data with search result
     * @see Query\QueryResult::__construct for the xml format. TODO: have the transport return a QueryResult?
     */
    public function querySQL($query, $limit = null, $offset = null);


    /************************************************************************
     * Methods for Version support.                                         *
     * Implement with UnsupportedRepositoryOperationException if you do not *
     * handle versioning                                                    *
     ************************************************************************/

    /**
     * Check-in item at path.
     *
     * @see VersionManager::checkin
     *
     * @param string $path
     * @return string path to the new version
     *
     * @throws PHPCR\UnsupportedRepositoryOperationException
     * @throws PHPCR\RepositoryException
     */
    public function checkinItem($path);

    /**
     * Check-out item at path.
     *
     * @see VersionManager::checkout
     *
     * @param string $path
     * @return void
     *
     * @throws PHPCR\UnsupportedRepositoryOperationException
     * @throws PHPCR\RepositoryException
     */
    public function checkoutItem($path);

    /**
     * Restore the item at versionPath to the location path
     *
     * TODO: This is incomplete. Needs batch processing to avoid chicken-and-egg problems
     *
     * @see VersionManager::restoreItem
     */
    public function restoreItem($removeExisting, $versionPath, $path);

    /**
     * Get the uuid of the version history node at $path
     *
     * @param string $path the path to the node we want the version
     * @return string uuid of the version history node
     *
     * TODO: Does this make any sense? We should maybe return the root version to make this more generic.
     */
    public function getVersionHistory($path);

}

