<?php

/**
 * The Session object provides read and (if implemented) write access to the
 * content of a particular workspace in the repository.
 *
 * The Session object is returned by Repository.login(). It encapsulates both
 * the authorization settings of a particular user (as specified by the passed
 * Credentials) and a binding to the workspace specified by the workspaceName
 * passed on login.
 *
 * Each Session object is associated one-to-one with a Workspace object. The
 * Workspace object represents a "view" of an actual repository workspace
 * entity as seen through the authorization settings of its associated Session.
 */
class jackalope_Session implements PHPCR_SessionInterface {
    protected $repository;
    protected $workspace;
    protected $objectManager;
    protected $credentials;
    protected $logout = false;

    /** creates the corresponding workspace */
    public function __construct(jackalope_Repository $repository, $workspaceName, PHPCR_SimpleCredentials $credentials, jackalope_TransportInterface $transport) {
        $this->repository = $repository;
        $this->workspace = jackalope_Factory::get('Workspace', array($this, $workspaceName));
        $this->objectManager = jackalope_Factory::get('ObjectManager', array($transport, $this));
        $this->credentials = $credentials;
    }
    /**
     * Returns the Repository object through which this session was acquired.
     *
     * @return PHPCR_RepositoryInterface a Repository object.
     * @api
     */
    public function getRepository() {
        return $this->repository;
    }

    /**
     * Gets the user ID associated with this Session. How the user ID is set is
     * up to the implementation, it may be a string passed in as part of the
     * credentials or it may be a string acquired in some other way. This method
     * is free to return an "anonymous user ID" or null.
     *
     * @return string The user id associated with this Session.
     * @api
     */
    public function getUserID() {
        return $this->credentials->getUserID(); //TODO: what if its not simple credentials? what about anonymous login?
    }

    /**
     * Returns the names of the attributes set in this session as a result of
     * the Credentials that were used to acquire it. Not all Credentials
     * implementations will contain attributes (though, for example,
     * SimpleCredentials does allow for them). This method returns an empty
     * array if the Credentials instance did not provide attributes.
     *
     * @return array A string array containing the names of all attributes passed in the credentials used to acquire this session.
     * @api
     */
    public function getAttributeNames() {
        return $this->credentials->getAttributeNames();
    }

    /**
     * Returns the value of the named attribute as an Object, or null if no
     * attribute of the given name exists. See getAttributeNames().
     *
     * @param string $name The name of an attribute passed in the credentials used to acquire this session.
     * @return object The value of the attribute or null if no attribute of the given name exists.
     * @api
     */
    public function getAttribute($name) {
        return $this->credentials->getAttribute($name);
    }

    /**
     * Returns the Workspace attached to this Session.
     *
     * @return PHPCR_WorkspaceInterface a Workspace object.
     * @api
     */
    public function getWorkspace() {
        return $this->workspace;
    }

    /**
     * Returns the root node of the workspace, "/". This node is the main access
     * point to the content of the workspace.
     *
     * @return PHPCR_NodeInterface The root node of the workspace: a Node object.
     * @throws RepositoryException if an error occurs.
     * @api
     */
    public function getRootNode() {
        return $this->getNode('/');
    }

    /**
     * Returns a new session in accordance with the specified (new) Credentials.
     * Allows the current user to "impersonate" another using incomplete or relaxed
     * credentials requirements (perhaps including a user name but no password, for
     * example), assuming that this Session gives them that permission.
     * The new Session is tied to a new Workspace instance. In other words, Workspace
     * instances are not re-used. However, the Workspace instance returned represents
     * the same actual persistent workspace entity in the repository as is represented
     * by the Workspace object tied to this Session.
     *
     * @param PHPCR_CredentialsInterface $credentials A Credentials object
     * @return PHPCR_SessionInterface a Session object
     * @throws PHPCR_LoginException if the current session does not have sufficient access to perform the operation.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function impersonate(PHPCR_CredentialsInterface $credentials) {
        throw new PHPCR_LoginException('Not supported');
    }

    /**
     * Returns the node specified by the given identifier. Applies to both referenceable
     * and non-referenceable nodes.
     *
     * @param string $id An identifier.
     * @return PHPCR_NodeInterface A Node.
     * @throws PHPCR_ItemNotFoundException if no node with the specified identifier exists or if this Session does not have read access to the node with the specified identifier.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function getNodeByIdentifier($id) {
        return $this->objectManager->getNode($id);
    }

    /**
     * Returns the node at the specified absolute path in the workspace. If no such
     * node exists, then it returns the property at the specified path.
     *
     * This method should only be used if the application does not know whether the
     * item at the indicated path is property or node. In cases where the application
     * has this information, either getNode(java.lang.String) or
     * getProperty(java.lang.String) should be used, as appropriate. In many repository
     * implementations the node and property-specific methods are likely to be more
     * efficient than getItem.
     *
     * @param string $absPath An absolute path.
     * @return PHPCR_ItemInterface
     * @throws PHPCR_PathNotFoundException if no accessible item is found at the specified path.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function getItem($absPath) {

        if(strpos($absPath,'/') !== 0) {
            throw new PHPCR_PathNotFoundException('It is forbidden to call getItem on session with a relative path');
        }

        if ($this->nodeExists($absPath)) {
            return $this->getNode($absPath);
        } else {
            return $this->getProperty($absPath);
        }
    }

    /**
     * Returns the node at the specified absolute path in the workspace.
     *
     * @param string $absPath An absolute path.
     * @return PHPCR_NodeInterface A node
     * @throws PHPCR_PathNotFoundException if no accessible node is found at the specified path.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function getNode($absPath) {
        return $this->objectManager->getNodeByPath($absPath);
    }

    /**
     * Returns the property at the specified absolute path in the workspace.
     *
     * @param string $absPath An absolute path.
     * @return PHPCR_PropertyInterface A property
     * @throws PHPCR_PathNotFoundException if no accessible property is found at the specified path.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function getProperty($absPath) {
        return $this->objectManager->getPropertyByPath($absPath);
    }

    /**
     * Returns true if an item exists at absPath and this Session has read
     * access to it; otherwise returns false.
     *
     * @param string $absPath An absolute path.
     * @return boolean a boolean
     * @throws PHPCR_RepositoryException if absPath is not a well-formed absolute path.
     * @api
     */
    public function itemExists($absPath) {
        if ($absPath == '/') return true;
        return $this->nodeExists($absPath) || $this->propertyExists($absPath);
    }

    /**
     * Returns true if a node exists at absPath and this Session has read
     * access to it; otherwise returns false.
     *
     * @param string $absPath An absolute path.
     * @return boolean a boolean
     * @throws PHPCR_RepositoryException if absPath is not a well-formed absolute path.
     * @api
     */
    public function nodeExists($absPath) {
        if ($absPath == '/') return true;
        try {
            //OPTIMIZE: avoid throwing and catching errors would improve performance if many node exists calls are made
            //would need to communicate to the lower layer that we do not want exceptions
            $this->getNode($absPath);
        } catch(Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Returns true if a property exists at absPath and this Session has read
     * access to it; otherwise returns false.
     *
     * @param string $absPath An absolute path.
     * @return boolean a boolean
     * @throws PHPCR_RepositoryException if absPath is not a well-formed absolute path.
     * @api
     */
    public function propertyExists($absPath) {
        try {
            //OPTIMIZE: avoid throwing and catching errors would improve performance if many node exists calls are made
            //would need to communicate to the lower layer that we do not want exceptions
            $this->getProperty($absPath);
        } catch(Exception $e) {
            return false;
        }
        return true;

    }

    /**
     * not implemented
     */
    public function move($srcAbsPath, $destAbsPath) {
        throw new jackalope_NotImplementedException('Write');
    }

    /**
     * not implemented
     */
    public function removeItem($absPath) {
        throw new jackalope_NotImplementedException('Write');
    }

    /**
     * not implemented
     */
    public function save() {
        throw new jackalope_NotImplementedException('Write');
    }

    /**
     * not implemented
     */
    public function refresh($keepChanges) {
        throw new jackalope_NotImplementedException('Write');
    }

    /**
     * Returns true if this session holds pending (that is, unsaved) changes;
     * otherwise returns false.
     *
     * @return boolean a boolean
     * @throws PHPCR_RepositoryException if an error occurs
     * @api
     */
    public function hasPendingChanges() {
        throw new jackalope_NotImplementedException('Write');
    }

    /**
     * not implemented
     */
    public function getValueFactory() {
        throw new jackalope_NotImplementedException('Write');
    }

    /**
     * For ACTION_READ, checks if the path is allowed to be read by the current session.
     *
     * If anything else/more than ACTION_READ is specified, will return false.
     *
     * @param string $absPath an absolute path.
     * @param string $actions a comma separated list of action strings.
     * @return boolean true if this Session has permission to perform the specified actions at the specified absPath.
     * @throws PHPCR_RepositoryException if an error occurs.
     * @api
     */
    public function hasPermission($absPath, $actions) {
        if ($actions == self::ACTION_READ) {
            throw new jackalope_NotImplementedException('TODO: check read permission');
           /*
            * The information returned through this method will only reflect the access
            * control status (both JCR defined and implementation-specific) and not
            * other restrictions that may exist, such as node type constraints. For
            * example, even though hasPermission may indicate that a particular Session
            * may add a property at /A/B/C, the node type of the node at /A/B may
            * prevent the addition of a property called C.
            */
        }
        //no write operations are supported.
        return false;
    }


    /**
     * If hasPermission returns false, throws the security exception
     *
     * @param string $absPath an absolute path.
     * @param string $actions a comma separated list of action strings.
     * @return void
     * @throws PHPCR_Security_AccessControlException If permission is denied.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function checkPermission($absPath, $actions) {
        if (! $this->hasPermission($absPath, $actions)) {
            throw new PHPCR_Security_AccessControlException($absPath);
        }
    }

    /**
     * not really anything right now
     *
     * @param string $methodName the name of the method.
     * @param object $target the target object of the operation.
     * @param array $arguments the arguments of the operation.
     * @return boolean FALSE if the operation cannot be performed, TRUE if the operation can be performed or if the repository cannot determine whether the operation can be performed.
     * @throws PHPCR_RepositoryException if an error occurs
     * @api
     */
    public function hasCapability($methodName, $target, array $arguments) {
        //we never determine wether operation can be performed as it is optional ;-)
        //TODO: could implement some
        return true;
    }

    /**
     * not implemented
     */
    public function getImportContentHandler($parentAbsPath, $uuidBehavior) {
        throw new jackalope_NotImplementedException('Write');
    }

    /**
     * not implemented
     */
    public function importXML($parentAbsPath, $in, $uuidBehavior) {
        throw new jackalope_NotImplementedException('Write');
    }

    /**
     * Serializes the node (and if $noRecurse is false, the whole subgraph) at
     * $absPath as an XML stream and outputs it to the supplied URI. The
     * resulting XML is in the system view form. Note that $absPath must be
     * the path of a node, not a property.
     *
     * If $skipBinary is true then any properties of PropertyType.BINARY will be serialized
     * as if they are empty. That is, the existence of the property will be serialized,
     * but its content will not appear in the serialized output (the <sv:value> element
     * will have no content). Note that in the case of multi-value BINARY properties,
     * the number of values in the property will be reflected in the serialized output,
     * though they will all be empty. If $skipBinary is false then the actual value(s)
     * of each BINARY property is recorded using Base64 encoding.
     *
     * If $noRecurse is true then only the node at $absPath and its properties, but not
     * its child nodes, are serialized. If $noRecurse is false then the entire subgraph
     * rooted at $absPath is serialized.
     *
     * If the user lacks read access to some subsection of the specified tree, that
     * section simply does not get serialized, since, from the user's point of view,
     * it is not there.
     *
     * The serialized output will reflect the state of the current workspace as
     * modified by the state of this Session. This means that pending changes
     * (regardless of whether they are valid according to node type constraints)
     * and all namespace mappings in the namespace registry, as modified by the
     * current session-mappings, are reflected in the output.
     *
     * The output XML will be encoded in UTF-8.
     *
     * @param string $absPath The path of the root of the subgraph to be serialized. This must be the path to a node, not a property
     * @param string $out The URI to which the XML serialization of the subgraph will be output.
     * @param boolean $skipBinary A boolean governing whether binary properties are to be serialized.
     * @param boolean $noRecurse A boolean governing whether the subgraph at absPath is to be recursed.
     * @return void
     * @throws PHPCR_PathNotFoundException if no node exists at absPath.
     * @throws RuntimeException if an error during an I/O operation occurs.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function exportSystemView($absPath, $out, $skipBinary, $noRecurse) {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Serializes the node (and if $noRecurse is false, the whole subgraph) at
     * $absPath as an XML stream and outputs it to the supplied URI. The
     * resulting XML is in the document view form. Note that $absPath must be
     * the path of a node, not a property.
     *
     * If $skipBinary is true then any properties of PropertyType.BINARY will be serialized as if
     * they are empty. That is, the existence of the property will be serialized, but its content
     * will not appear in the serialized output (the value of the attribute will be empty). If
     * $skipBinary is false then the actual value(s) of each BINARY property is recorded using
     * Base64 encoding.
     *
     * If $noRecurse is true then only the node at $absPath and its properties, but not its
     * child nodes, are serialized. If $noRecurse is false then the entire subgraph rooted at
     * $absPath is serialized.
     *
     * If the user lacks read access to some subsection of the specified tree, that section
     * simply does not get serialized, since, from the user's point of view, it is not there.
     *
     * The serialized output will reflect the state of the current workspace as modified by
     * the state of this Session. This means that pending changes (regardless of whether they
     * are valid according to node type constraints) and all namespace mappings in the
     * namespace registry, as modified by the current session-mappings, are reflected in
     * the output.
     *
     * The output XML will be encoded in UTF-8.
     *
     * @param string $absPath The path of the root of the subgraph to be serialized. This must be the path to a node, not a property
     * @param string $out The URI to which the XML serialization of the subgraph will be output.
     * @param boolean $skipBinary A boolean governing whether binary properties are to be serialized.
     * @param boolean $noRecurse A boolean governing whether the subgraph at absPath is to be recursed.
     * @return void
     * @throws PHPCR_PathNotFoundException if no node exists at absPath.
     * @throws RuntimeException if an error during an I/O operation occurs.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function exportDocumentView($absPath, $out, $skipBinary, $noRecurse) {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Within the scope of this Session, this method maps uri to prefix. The
     * remapping only affects operations done through this Session. To clear
     * all remappings, the client must acquire a new Session.
     * All local mappings already present in the Session that include either
     * the specified prefix or the specified uri are removed and the new mapping
     * is added.
     *
     * @param string $prefix a string
     * @param string $uri a string
     * @return void
     * @throws PHPCR_NamespaceException if an attempt is made to map a namespace URI to a prefix beginning with the characters "xml" (in any combination of case) or if an attempt is made to map either the empty prefix or the empty namespace (i.e., if either $prefix or $uri are the empty string).
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function setNamespacePrefix($prefix, $uri) {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns all prefixes currently mapped to URIs in this Session.
     *
     * @return array a string array
     * @throws PHPCR_RepositoryException if an error occurs
     * @api
     */
    public function getNamespacePrefixes() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns the URI to which the given prefix is mapped as currently set in
     * this Session.
     *
     * @param string $prefix a string
     * @return string a string
     * @throws PHPCR_NamespaceException if the specified prefix is unknown.
     * @throws PHPCR_RepositoryException if another error occurs
     * @api
     */
    public function getNamespaceURI($prefix) {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns the prefix to which the given uri is mapped as currently set in
     * this Session.
     *
     * @param string $uri a string
     * @return string a string
     * @throws PHPCR_NamespaceException if the specified uri is unknown.
     * @throws PHPCR_RepositoryException - if another error occurs
     * @api
     */
    public function getNamespacePrefix($uri) {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Releases all resources associated with this Session. This method should
     * be called when a Session is no longer needed.
     *
     * @return void
     * @api
     */
    public function logout() {
        //TODO anything to do on logout?
        //OPTIMIZATION: flush object manager
        $this->logout = true;
    }

    /**
     * Returns true if this Session object is usable by the client. Otherwise,
     * returns false.
     * A usable Session is one that is neither logged-out, timed-out nor in
     * any other way disconnected from the repository.
     *
     * @return boolean true if this Session is usable, false otherwise.
     * @api
     */
    public function isLive() {
        return ! $this->logout;
    }

    /**
     * Returns the access control manager for this Session.
     *
     * @return PHPCR_Security_AccessControlManager the access control manager for this Session
     * @throws PHPCR_UnsupportedRepositoryOperationException if access control is not supported.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function getAccessControlManager() {
        throw new PHPCR_UnsupportedRepositoryOperationException();
    }

    /**
     * Returns the retention and hold manager for this Session.
     *
     * @return PHPCR_Retention_RetentionManagerInterface the retention manager for this Session.
     * @throws PHPCR_UnsupportedRepositoryOperationException if retention and hold are not supported.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function getRetentionManager() {
        throw new PHPCR_UnsupportedRepositoryOperationException();
    }

    /**
     * Implementation specific: The object manager is also used by other components, i.e. the QueryManager
     */
    public function getObjectManager() {
        return $this->objectManager;
    }
    /**
     * Implementation specific: The transport implementation is also used by other components, i.e. the NamespaceRegistry
     */
    public function getTransport() {
        return $this->objectManager->getTransport();
    }
}
