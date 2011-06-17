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
 * Implementors can expect Jackalope to only pass normalized absolute paths
 * to the transport. What still has to be tested is if the paths contain no
 * invalid characters according to
 * <a href="http://www.day.com/specs/jcr/2.0/3_Repository_Model.html#3.2.2%20Local%20Names">the specification</a>
 * and backend specific restrictions.
 *
 * This interface is now synchronized with what we had for davex as per 2011-04-13
 * TODO: keep this in sync with Transport/Davex/Client.php
 * TODO: add references to all phpcr api methods that use each transport method for additional doc
 * TODO: add methods for all features. split into one interface per feature and transport implements just does it actually supports
 *
 * @package jackalope
 * @subpackage transport
 */
interface TransportInterface
{
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
     * @throws \PHPCR\NoSuchWorkspaceException if the specified workspaceName is not recognized
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
     * Returns all additional namespaces. Does not return the ones defined as
     * constants in \PHPCR\NamespaceRegistryInterface
     *
     * @return array Associative array of prefix => uri
     *
     * @throws \PHPCR\RepositoryException if not logged in
     */
    public function getNamespaces();


    /**
     * Get the node from an absolute path
     *
     * Returns a json_decode stdClass structure that contains two fields for
     * each property and one field for each child.
     * A child is just containing an empty class as value (in the future we
     * could use this for eager loading with recursive structure).
     * A property consists of a field named as the property is and a value that
     * is the property value, plus a second field with the same name but
     * prefixed with a colon that has a type specified as value (out of the
     * string constants from PropertyType)
     *
     * For binary properties, the value of the type declaration is not the type
     * but the length of the binary, thus integer instead of string.
     * There is no value field for binary data (to avoid loading large amount
     * of unneeded data)
     * Use getBinaryStream to get the actual data of a binary property.
     *
     * There is a couple of "magic" properties:
     * <ul>
     *   <li>jcr:uuid - the unique id of the node</li>
     *   <li>jcr:primaryType - name of the primary type</li>
     *   <li>jcr:mixinTypes - comma separated list of mixin types</li>
     *   <li>jcr:index - the index of same name siblings</li>
     * </ul>
     *
     * @example Return struct
     * <code>
     * object(stdClass)#244 (4) {
     *      ["jcr:uuid"]=>
     *          string(36) "64605997-e298-4334-a03e-673fc1de0911"
     *      [":jcr:primaryType"]=>
     *          string(4) "Name"
     *      ["jcr:primaryType"]=>
     *          string(8) "nt:unstructured"
     *      ["myProperty"]=>
     *          string(4) "test"
     *      [":myProperty"]=>
     *          string(5) "String" //one of \PHPCR\PropertyTypeInterface::TYPENAME_NAME
     *      [":myBinary"]=>
     *          int 1538    //length of binary file, no "myBinary" field present
     *      ["childNodeName"]=>
     *          object(stdClass)#152 (0) {}
     *      ["otherChild"]=>
     *          object(stdClass)#153 (0) {}
     * }
     * </code>
     *
     * Note: the reason to use json_decode with associative = false is that the
     * array version can not distinguish between
     *   ['foo', 'bar'] and {0: 'foo', 1: 'bar'}
     * The first are properties, but the later is a list of children nodes.
     *
     * @param string $path Absolute path to the node.
     * @return array associative array for the node (decoded from json with associative = true)
     *
     * @throws \PHPCR\ItemNotFoundException If the item at path was not found
     * @throws \PHPCR\RepositoryException if not logged in
     */
    public function getNode($path);

    /**
     * Get the nodes from an array of absolute paths
     *
     * @param array $path Absolute paths to the nodes.
     * @return array associative array for the node (decoded from json with associative = true)
     *
     * @throws \PHPCR\RepositoryException if not logged in
     */
    public function getNodes($paths);

    /**
     * Get the property stored at an absolute path.
     *
     * Same format as getNode with just one property. Again, for binary
     * properties just returns the size and not the actual data.
     *
     * @return stdClass a json struct with the property type and property value(s)
     *
     * @see self::getNode($path)
     */
    public function getProperty($path);

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
     * Retrieve a stream of a binary property value
     *
     * @param $path The path to the property with the binary data
     * @return resource with binary data
     */
    public function getBinaryStream($path);



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
     * @param   string  $srcWorkspace   The workspace where the source node can be found or null for current workspace
     * @return void
     *
     * @link http://www.ietf.org/rfc/rfc2518.txt
     * @see \Jackalope\Workspace::copy
     */
    public function copyNode($srcAbsPath, $dstAbsPath, $srcWorkspace = null);

    /**
     * Clones the subgraph at the node srcAbsPath in srcWorkspace to the new
     * location at destAbsPath in this workspace.
     *
     * There may be no node at dstAbsPath
     * This method does not need to load the node but can execute the clone
     * directly in the storage.
     *
     * @param   string  $srcAbsPath     Absolute source path to the node
     * @param   string  $dstAbsPath     Absolute destination path (must include the new node name)
     * @param   string  $srcWorkspace   The workspace where the source node can be found
     *
     * @return void
     *
     * @link http://www.ietf.org/rfc/rfc2518.txt
     * @see \Jackalope\Workspace::cloneFrom
     */
    public function cloneFrom($srcWorkspace, $srcAbsPath, $destAbsPath, $removeExisting);

    /**
     * Moves a node from src to dst
     *
     * @param   string  $srcAbsPath     Absolute source path to the node
     * @param   string  $dstAbsPath     Absolute destination path (must NOT include the new node name)
     * @return void
     *
     * @link http://www.ietf.org/rfc/rfc2518.txt
     * @see \Jackalope\Workspace::moveNode
     */
    public function moveNode($srcAbsPath, $dstAbsPath);

    /**
     * Deletes a node and the whole subtree under it
     *
     * @param string $path Absolute path to the node
     * @return bool true on success
     *
     * @throws \PHPCR\PathNotFoundException if the item is already deleted on
     *      the server. This should not happen if ObjectManager is correctly
     *      checking.
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
     * Transport stores the node at its path, with all properties and all children
     *
     * @param \PHPCR\NodeInterface $node the node to store
     * @return bool true on success
     *
     * @throws \PHPCR\RepositoryException if not logged in
     */
    public function storeNode(\PHPCR\NodeInterface $node);

    /**
     * Stores a property to its absolute path
     *
     * @param \PHPCR\PropertyInterface
     * @return bool true on success
     *
     * @throws \PHPCR\RepositoryException if not logged in
     */
    public function storeProperty(\PHPCR\PropertyInterface $property);

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


    /*********************************
     * Methods for NodeType support. *
     *********************************/

    /**
     * Pass the node type manager into the transport to be used for validation and such.
     *
     * @param \Jackalope\NodeTypeManager $nodeTypeManager
     * @return void
     */
    public function setNodeTypeManager($nodeTypeManager);

    /**
     * Get node types, either filtered or all
     *
     * @param array string names of node types to fetch, if empty array all node types are retrieved
     *
     * @return array with the definitions (see Jackalope\NodeTypeDefinition::fromArray for what is expected)
     *
     * @throws \PHPCR\RepositoryException if not logged in
     * @see Jackalope\NodeTypeDefinition::fromArray
     */
    public function getNodeTypes($nodeTypes = array());

    /**
     * Register namespaces and new node types or update node types based on a
     * jackrabbit cnd string
     *
     * TODO: change this to xml string (format described at the end of http://jackrabbit.apache.org/node-type-notation.html) we even have the parser in the node type manager
     *
     * @param $cnd The cnd string
     * @param boolean $allowUpdate whether to fail if node already exists or to update it
     *
     * @return bool true on success
     *
     * @see \Jackalope\NodeTypeManager::registerNodeTypesCnd
     */
    public function registerNodeTypesCnd($cnd, $allowUpdate);

    /**
     * Register a list of node types with the storage backend
     *
     * @param array $types a list of \PHPCR\NodeType\NodeTypeDefinitionInterface objects
     * @param boolean $allowUpdate whether to fail if node already exists or to update it
     *
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
     * The language must be among those returned by getSupportedQueryLanguages
     *
     * Implementors: Expose all information required by the transport layers to
     * execute the query with getters.
     *
     * array(
     *     //row 1
     *     array(
     *         //column1
     *         array('dcr:name' => 'value1',
     *               'dcr:value' => 'value2',
     *               'dcr:selectorName' => 'value3' //optional
     *         ),
     *         //column 2...
     *     ),
     *     //row 2
     *     array(...
     * )
     *
     * @param \PHPCR\Query\QueryInterface $query the query object
     * @return array data with search result. TODO: what to return? should be some simple array
     * @see Query\QueryResult::__construct for the xml format. TODO: have the transport return a QueryResult?
     */
    public function query(\PHPCR\Query\QueryInterface $query);

    //TODO: getSupportedQueryLanguages

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

    /**
     * Returns the path of all accessible REFERENCE properties in the workspace that point to the node
     *
     * @param string $path
     * @param string $name name of referring REFERENCE properties to be returned; if null then all referring REFERENCEs are returned
     * @return array
     */
    public function getReferences($path, $name = null);

    /**
     * Returns the path of all accessible WEAKREFERENCE properties in the workspace that point to the node
     *
     * @param string $path
     * @param string $name name of referring WEAKREFERENCE properties to be returned; if null then all referring WEAKREFERENCEs are returned
     * @return array
     */
    public function getWeakReferences($path, $name = null);

    /**
     * Return the permissions of the current session on the node given by path.
     * The result of this function is an array of zero, one or more strings from add_node, read, remove, set_property.
     *
     * @param string $path the path to the node we want to check
     * @return array of string
     */
    public function getPermissions($path);
}
