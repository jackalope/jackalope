<?php

namespace Jackalope\Transport;

use PHPCR\CredentialsInterface;

use Jackalope\NodeType\NodeTypeManager;

/**
 * Core transport operations. Every transport must implement this interface. It
 * defines the minimal operations required for Jackalope to work.
 *
 * Note that the JCR <a href="http://www.day.com/specs/jcr/2.0/24_Repository_Compliance.html">Repository
 * Compliance</a> specification defines a larger set of functions, but we want
 * Jackalope to even work with very minimalistic backends.
 *
 * See the README.md in this folder for general information about the transport
 * layer.

 * This interface is now synchronized with what we had for davex as per 2011-04-13
 * TODO: keep this in sync with Transport/Jackrabbit/Client.php
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 */
interface TransportInterface
{
    /**
     * Get all repository descriptors
     *
     * This may be called before login or accessing any specific workspace.
     * With this, you can get some information without being logged in.
     *
     * Must return at least the constants defined in \PHPCR\RepositoryInterface
     * Doc about each constant is found there. Implementations may add their
     * own constants.
     *
     * The transport has to make sure the correct boolean values are set for
     * optional features. Jackalope will rely on the interface implementation,
     * but client code could check the descriptors and be confused if you
     * announce invalid capabilities here.
     *
     * @return Array with name => value/array of value for the descriptors
     *
     * @throws \PHPCR\RepositoryException if error occurs
     *
     * @see http://www.day.com/specs/jcr/2.0/24_Repository_Compliance.html#24.2%20Repository%20Descriptors
     * @see \PHPCR\RepositoryInterface
     */
    function getRepositoryDescriptors();

    /**
     * Returns the workspace names that can be used when logging in.
     *
     * @return array List of workspaces that can be specified on login
     */
    function getAccessibleWorkspaceNames();

    /**
     * Set this transport to a specific credential and a workspace.
     *
     * This can only be called once. To connect to another workspace or with
     * another credential, use a fresh instance of transport.
     *
     * What implementation of credentials is supported is transport specific.
     *
     * @param CredentialsInterface the credentials to connect with the
     *      backend
     * @param workspaceName The workspace name to connect to.
     *
     * @return true on success (exceptions on failure)
     *
     * @throws \PHPCR\LoginException if authentication or authorization (for
     *      the specified workspace) fails
     * @throws \PHPCR\NoSuchWorkspaceException if the specified workspaceName
     *      is not recognized
     * @throws \PHPCR\RepositoryException if another error occurs
     */
    function login(CredentialsInterface $credentials, $workspaceName);

    /***********************************************************************
     * all methods from here below require that login is called first. the *
     * behaviour of transport is undefined if this is not respected.       *
     ***********************************************************************/

    /******************************************
     * Methods for session management support *
     ******************************************/

    /**
     * Releases all resources associated with this Session.
     *
     * This method is called on $session->logout
     * Implementations can use it to close database connections and similar.
     *
     * @return void
     */
    function logout();

    /****************************
     * Methods for read support *
     ****************************/

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
    function getNamespaces();

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
     *
     * @return array associative array for the node (decoded from json with
     *      associative = true)
     *
     * @throws \PHPCR\ItemNotFoundException If the item at path was not found
     * @throws \PHPCR\RepositoryException if not logged in
     */
    function getNode($path);

    /**
     * Get the nodes from an array of absolute paths.
     *
     * This is an optimization over getNode to get many nodes in one call. If
     * the transport implementation does not optimize, it can just loop over the
     * paths and call getNode repeatedly.
     *
     * @param array $path Absolute paths to the nodes.
     *
     * @return array associative array for the node (decoded from json with
     *      associative = true)
     *
     * @throws \PHPCR\RepositoryException if not logged in
     */
    function getNodes($paths);

    /**
     * Get the property stored at an absolute path.
     *
     * Same format as getNode with just one property. Again, for binary
     * properties just returns the size and not the actual data.
     *
     * @return stdClass a json struct with the property type and property
     *      value(s)
     *
     * @see TransportInterface::getNode($path)
     */
    function getProperty($path);

    /**
     * Get the node path from a JCR uuid
     *
     * @param string $uuid the id in JCR format
     *
     * @return string Absolute path to the node (not the node itself!)
     *
     * @throws \PHPCR\ItemNotFoundException if the backend does not know the
     *      uuid
     * @throws \PHPCR\RepositoryException if not logged in
     */
    function getNodePathForIdentifier($uuid);

    /**
     * Retrieve a stream of a binary property value
     *
     * @param $path The path to the property with the binary data
     *
     * @return resource with binary data
     */
    function getBinaryStream($path);

    /****************************************************************************
     * References reading                                                       *
     * if you really can't support these, throw UnsupportedRepositoryException  *
     ****************************************************************************/

    /**
     * Returns the path of all accessible REFERENCE properties in the workspace
     * that point to the node
     *
     * @param string $path
     * @param string $name name of referring REFERENCE properties to be returned;
     *       if null then all referring REFERENCEs are returned
     *
     * @return array
     */
    function getReferences($path, $name = null);

    /**
     * Returns the path of all accessible WEAKREFERENCE properties in the
     * workspace that point to the node
     *
     * @param string $path
     * @param string $name name of referring WEAKREFERENCE properties to be
     *      returned; if null then all referring WEAKREFERENCEs are returned
     *
     * @return array
     */
    function getWeakReferences($path, $name = null);

    /***********************************
     * Methods for NodeType discovery. *
     ***********************************/

    /**
     * Pass the node type manager into the transport to be used for validation
     * and such.
     *
     * @param NodeTypeManager $nodeTypeManager
     *
     * @return void
     */
    function setNodeTypeManager($nodeTypeManager);

    /**
     * Get node types, either filtered or all.
     *
     * If the transport does not support registering new node types, it can
     * just return types from the hard coded definition at
     * Jackalope\NodeType\PHPCR2StandardNodeTypes
     *
     * @param array string names of node types to fetch, if empty array all
     *      node types are retrieved
     *
     * @return array with the definitions (see
     *      Jackalope\NodeTypeDefinition::fromArray for what is expected)
     *
     * @throws \PHPCR\RepositoryException if not logged in
     *
     * @see Jackalope\NodeTypeDefinition::fromArray
     */
    function getNodeTypes($nodeTypes = array());

}
