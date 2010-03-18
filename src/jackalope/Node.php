<?php

class jackalope_Node extends jackalope_Item implements PHPCR_NodeInterface {

    protected $index = 1;
    protected $primaryType;

    /** TODO: what is this? property names or also property values? */
    protected $properties = array();
    /**
     * list of children names
     */
    protected $nodes = array();

    public function __construct($rawData, $path,  $session, $objectManager) {
        parent::__construct($rawData, $path,  $session, $objectManager);
        $this->isNode = true;

        //TODO: determine the index if != 1

        foreach ($rawData as $key => $value) {
            if (is_object($value)) {
                array_push($this->nodes, $key);
            } else {
                if ( 0 === strpos($key, ':')) continue; //It's a property type

                switch ($key) {
                    case 'jcr:index':
                        $this->index = $value;
                        break;
                    case 'jcr:primaryType':
                        $this->primaryType = $value;
                        break;
                    //TODO: more special information?
                    default:
                        array_push($this->properties, $key);
                        break;
                }
            }
        }
    }

    /**
     * not implemented
     */
    public function addNode($relPath, $primaryNodeTypeName = NULL, $identifier = NULL) {
        throw new jackalope_NotImplementedException('Write');
    }

    /**
     * not implemented
     */
    public function orderBefore($srcChildRelPath, $destChildRelPath) {
        throw new jackalope_NotImplementedException('Write');
    }

    /**
     * not implemented
     */
    public function setProperty($name, $value, $type = NULL) {
        throw new jackalope_NotImplementedException('Write');
    }

    /**
     * Returns the node at relPath relative to this node.
     * If relPath contains a path element that refers to a node with same-name
     * sibling nodes without explicitly including an index using the array-style
     * notation ([x]), then the index [1] is assumed (indexing of same name
     * siblings begins at 1, not 0, in order to preserve compatibility with XPath).
     *
     * Within the scope of a single Session object, if a Node object has been
     * acquired, any subsequent call of getNode reacquiring the same node must
     * return a Node object reflecting the same state as the earlier Node object.
     * Whether this object is actually the same Node instance, or simply one
     * wrapping the same state, is up to the implementation.
     *
     * @param string $relPath The relative path of the node to retrieve.
     * @return PHPCR_NodeInterface The node at relPath.
     * @throws PHPCR_PathNotFoundException If no node exists at the specified path or the current Session does not read access to the node at the specified path.
     * @throws PHPCR_RepositoryException If another error occurs.
     * @api
     */
    public function getNode($relPath) {
        //TODO: should we resolve ../ in path? is that even allowed?
        return $this->objectManager->getNodeByPath($this->path . "/$relPath");
    }

    /**
     * If $filter is a string:
     * Gets all child nodes of this node accessible through the current Session
     * that match namePattern (if no pattern is given, all accessible child nodes
     * are returned). Does not include properties of this Node. The pattern may
     * be a full name or a partial name with one or more wildcard characters ("*"),
     * or a disjunction (using the "|" character to represent logical OR) of these.
     * For example,
     *  N->getNodes("jcr:* | myapp:report | my doc")
     * would return a NodeIterator holding all accessible child nodes of N that
     * are either called 'myapp:report', begin with the prefix 'jcr:' or are
     * called 'my doc'.
     *
     * The substrings within the pattern that are delimited by "|" characters
     * and which may contain wildcard characters ("*") are called "globs".
     *
     * Note that leading and trailing whitespace around a glob is ignored, but
     * whitespace within a disjunct forms part of the pattern to be matched.
     *
     *If $filter is an array:
     * Gets all child nodes of this node accessible through the current
     * Session that match one or more of the $filter strings in the passed
     * array.
     *
     * A glob may be a full name or a partial name with one or more wildcard
     * characters ("*"). For example,
     *  N->getNodes(array("jcr:*", "myapp:report", "my doc"))
     * would return a NodeIterator holding all accessible child nodes of N that
     * are either called 'myapp:report', begin with the prefix 'jcr:' or are
     * called 'my doc'.
     *
     * Note that unlike in the case of the getNodes(<string>) leading and
     * trailing whitespace around a glob is not ignored.
     *
     *
     * The pattern is matched against the names (not the paths) of the immediate
     * child nodes of this node.
     *
     * If this node has no accessible matching child nodes, then an empty
     * iterator is returned.
     *
     * The same reacquisition semantics apply as with getNode($relPath).
     *
     * @param string|array $filter a name pattern or an array of globbing strings.
     * @return PHPCR_NodeIteratorInterface a NodeIterator over all (matching) child Nodes
     * @throws PHPCR_RepositoryException If an unexpected error occurs.
     * @api
     */
    public function getNodes($filter = NULL) {
        $names = self::filterNames($filter, $this->nodes);
        foreach($names as $name) {
            //OPTIMIZE: batch get nodes
            $result[] = $this->getNode($name);
        }
        return new jackalope_NodeIterator($result);
    }

    /**
     * Returns the property at relPath relative to this node. The same
     * reacquisition semantics apply as with getNode(String).
     *
     * @param string $relPath The relative path of the property to retrieve.
     * @return PHPCR_PropertyInterface The property at relPath.
     * @throws PHPCR_PathNotFoundException if no property exists at the specified path or if the current Session does not have read access to the specified property.
     * @throws PHPCR_RepositoryException If another error occurs.
     * @api
     */
    public function getProperty($relPath) {
        throw new jackalope_NotImplementedException();
    }

    /**
     * If $filter is a string:
     * Gets all properties of this node accessible through the current Session
     * that match namePattern (if no pattern is given, all accessible properties
     * are returned). Does not include child nodes of this node. The pattern may
     * be a full name or a partial name with one or more wildcard characters ("*"),
     * or a disjunction (using the "|" character to represent logical OR) of
     * these. For example,
     * N.getProperties("jcr:* | myapp:name | my doc")
     * would return a PropertyIterator holding all accessible properties of N
     * that are either called 'myapp:name', begin with the prefix 'jcr:' or are
     * called 'my doc'.
     *
     * The substrings within the pattern that are delimited by "|" characters
     * and which may contain wildcard characters ("*") are called globs.
     *
     * Note that leading and trailing whitespace around a glob is ignored, but
     * whitespace within a disjunct forms part of the pattern to be matched.
     *
     * If $filter is an array:
     * Gets all properties of this node accessible through the current
     * Session that match one or more of the $filter strings in the passed array.
     *
     * A glob may be a full name or a partial name with one or more wildcard
     * characters ("*"). For example,
     *  N->getProperties(array("jcr:*", "myapp:report", "my doc"))
     * would return a PropertyIterator holding all accessible properties of N
     * that are either called 'myapp:report', begin with the prefix 'jcr:' or
     * are called 'my doc'.
     *
     * Note that unlike in the case of getProperties(<string>) leading and
     * trailing whitespace around a glob is not ignored.
     *
     *
     * The pattern is matched against the names (not the paths) of the immediate
     * child properties of this node.
     *
     * If this node has no accessible matching properties, then an empty iterator
     * is returned.
     *
     * The same reacquisition semantics apply as with getNode(String).
     *
     * @param string|array $filter a name pattern
     * @return PHPCR_PropertyIteratorInterface a PropertyIterator
     * @throws PHPCR_RepositoryException If an unexpected error occurs.
     * @api
     */
    public function getProperties($filter = NULL) {
        $names = self::filterNames($filter, $this->properties); //TODO: is this also just properties names?
        foreach($names as $name) {
            //OPTIMIZE: batch get properties? or do we already have them?
            $result[] = $this->getProperty($name);
        }
        return new jackalope_PropertyIterator($result);
    }

    /**
     * Returns the primary child item of this node. The primary node type of this
     * node may specify one child item (child node or property) of this node as
     * the primary child item. This method returns that item.
     *
     * In cases where the primary child item specifies the name of a set same-name
     * sibling child nodes, the node returned will be the one among the same-name
     * siblings with index [1].
     *
     * The same reacquisition semantics apply as with getNode(String).
     *
     * @return PHPCR_ItemInterface the primary child item.
     * @throws PHPCR_ItemNotFoundException if this node does not have a primary child item, either because none is declared in the node type or because a declared primary item is not present on this node instance, or because none accessible through the current Session
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function getPrimaryItem() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns the identifier of this node. Applies to both referenceable and
     * non-referenceable nodes.
     *
     * @return string the identifier of this node
     * @throws PHPCR_RepositoryException If an error occurs.
     * @api
     */
    public function getIdentifier() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * This method returns the index of this node within the ordered set of its
     * same-name sibling nodes. This index is the one used to address same-name
     * siblings using the square-bracket notation, e.g., /a[3]/b[4]. Note that
     * the index always starts at 1 (not 0), for compatibility with XPath. As a
     * result, for nodes that do not have same-name-siblings, this method will
     * always return 1.
     *
     * @return integer The index of this node within the ordered set of its same-name sibling nodes.
     * @throws PHPCR_RepositoryException if an error occurs.
     * @api
     */
    public function getIndex() {
        return $this->index;
    }

    /**
     * This method returns all REFERENCE properties that refer to this node, have
     * the specified name and that are accessible through the current Session.
     * If the name parameter is null then all referring REFERENCES are returned
     * regardless of name.
     *
     * Some implementations may only return properties that have been persisted.
     * Some may return both properties that have been persisted and those that
     * have been dispatched but not persisted (for example, those saved within a
     * transaction but not yet committed) while others implementations may
     * return these two categories of property as well as properties that are
     * still pending and not yet dispatched.
     *
     * In implementations that support versioning, this method does not return
     * properties that are part of the frozen state of a version in version storage.
     *
     * If this node has no referring properties with the specified name, an empty
     * iterator is returned.
     *
     * @param string $name name of referring REFERENCE properties to be returned; if null then all referring REFERENCEs are returned
     * @return PHPCR_PropertyIteratorInterface A PropertyIterator.
     * @throws PHPCR_RepositoryException if an error occurs
     * @api
     */
    public function getReferences($name = NULL) {
        throw new jackalope_NotImplementedException();
    }

    /**
     * This method returns all WEAKREFERENCE properties that refer to this node,
     * have the specified name and that are accessible through the current Session.
     * If the name parameter is null then all referring WEAKREFERENCE are returned
     * regardless of name.
     *
     * Some write implementations may only return properties that have been
     * saved (in a transactional setting this includes both those properties that
     * have been saved but not yet committed, as well as properties that have
     * been committed). Other level 2 implementations may additionally return
     * properties that have been added within the current Session but are not yet
     * saved.
     *
     * In implementations that support versioning, this method does not return
     * properties that are part of the frozen state of a version in version storage.
     *
     * If this node has no referring properties with the specified name, an empty
     * iterator is returned.
     *
     * @param string $name name of referring WEAKREFERENCE properties to be returned; if null then all referring WEAKREFERENCEs are returned
     * @return PHPCR_PropertyIteratorInterface A PropertyIterator.
     * @throws PHPCR_RepositoryException if an error occurs
     * @api
     */
    public function getWeakReferences($name = NULL) {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Indicates whether a node exists at relPath
     * Returns true if a node accessible
     * through the current Session exists at relPath and false otherwise.
     *
     * @param string $relPath The path of a (possible) node.
     * @return boolean true if a node exists at relPath; false otherwise.
     * @throws PHPCR_RepositoryException if an error occurs.
     * @api
     */
    public function hasNode($relPath) {
        if (false === strpos($relPath, '/')) {
            //TODO: Fetch real things
            throw new jackalope_NotImplementedException('Only direct children at the moment');
        } else {
            return isset($this->nodes[$relPath]);
        }
    }

    /**
     * Indicates whether a property exists at relPath Returns true if a property
     * accessible through the current Session exists at relPath and false otherwise.
     *
     * @param string $relPath The path of a (possible) property.
     * @return boolean true if a property exists at relPath; false otherwise.
     * @throws PHPCR_RepositoryException if an error occurs.
     * @api
     */
    public function hasProperty($relPath) {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Indicates whether this node has child nodes. Returns true if this node has
     * one or more child nodes accessible through the current Session; false otherwise.
     *
     * @return boolean true if this node has one or more child nodes; false otherwise.
     * @throws PHPCR_RepositoryException if an error occurs.
     * @api
     */
    public function hasNodes() {
        return (! empty($this->nodes));
    }

    /**
     * Indicates whether this node has properties. Returns true if this node has
     * one or more properties accessible through the current Session; false otherwise.
     *
     * @return boolean true if this node has one or more properties; false otherwise.
     * @throws PHPCR_RepositoryException if an error occurs.
     * @api
     */
    public function hasProperties() {
        return (! empty($this->properties));
    }

    /**
     * Returns the primary node type in effect for this node. Which NodeType is
     * returned when this method is called on the root node of a workspace is up
     * to the implementation.
     *
     * @return PHPCR_NodeType_NodeTypeInterface a NodeType object.
     * @throws PHPCR_RepositoryException if an error occurs
     * @api
     */
    public function getPrimaryNodeType() {
        throw new jackalope_NotImplementedException(); //create nodetype instance for $this->primaryType
    }

    /**
     * Returns an array of NodeType objects representing the mixin node types in
     * effect for this node. This includes only those mixin types explicitly
     * assigned to this node. It does not include mixin types inherited through
     * the addition of supertypes to the primary type hierarchy or through the
     * addition of supertypes to the type hierarchy of any of the declared mixin
     * types.
     *
     * @return array of PHPCR_NodeType_NodeTypeInterface objects.
     * @throws PHPCR_RepositoryException if an error occurs
     * @api
     */
    public function getMixinNodeTypes() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns true if this node is of the specified primary node type or mixin
     * type, or a subtype thereof. Returns false otherwise.
     * This method respects the effective node type of the node.
     *
     * @param string $nodeTypeName the name of a node type.
     * @return boolean true if this node is of the specified primary node type or mixin type, or a subtype thereof. Returns false otherwise.
     * @throws PHPCR_RepositoryException If an error occurs.
     * @api
     */
    public function isNodeType($nodeTypeName) {
        throw new jackalope_NotImplementedException();
    }

    /**
     * not implemented
     */
    public function setPrimaryType($nodeTypeName) {
        throw new jackalope_NotImplementedException('Write');
    }

    /**
     * not implemented
     */
    public function addMixin($mixinName) {
        throw new jackalope_NotImplementedException('Write');
    }

    /**
     * not implemented
     */
    public function removeMixin($mixinName) {
        throw new jackalope_NotImplementedException('Write');
    }

    /**
     * not implemented
     */
    public function canAddMixin($mixinName) {
        throw new jackalope_NotImplementedException('Write');
    }

    /**
     * Returns the node definition that applies to this node. In some cases there
     * may appear to be more than one definition that could apply to this node.
     * However, it is assumed that upon creation of this node, a single particular
     * definition was used and it is that definition that this method returns.
     * How this governing definition is selected upon node creation from among
     * others which may have been applicable is an implementation issue and is
     * not covered by this specification. The NodeDefinition returned when this
     * method is called on the root node of a workspace is also up to the
     * implementation.
     *
     * @return PHPCR_NodeType_NodeDefinitionInterface a NodeDefinition object.
     * @throws PHPCR_RepositoryException if an error occurs.
     * @api
     */
    public function getDefinition() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * not implemented
     */
    public function update($srcWorkspace) {
        throw new jackalope_NotImplementedException('Write');
    }

    /**
     * Returns the absolute path of the node in the specified workspace that
     * corresponds to this node.
     *
     * @param string $workspaceName the name of the workspace.
     * @return string the absolute path to the corresponding node.
     * @throws PHPCR_ItemNotFoundException if no corresponding node is found.
     * @throws PHPCR_NoSuchWorkspaceException if the workspace is unknown.
     * @throws PHPCR_AccessDeniedException if the current session has insufficient access capabilities to perform this operation.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function getCorrespondingNodePath($workspaceName) {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns an iterator over all nodes that are in the shared set of this node.
     * If this node is not shared then the returned iterator contains only this node.
     *
     * @return PHPCR_NodeIteratorInterface a NodeIterator
     * @throws PHPCR_RepositoryException if an error occurs.
     * @api
     */
    public function getSharedSet() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * not implemented
     */
    public function removeSharedSet() {
        throw new jackalope_NotImplementedException('Write');
    }

    /**
     * not implemented
     */
    public function removeShare() {
        throw new jackalope_NotImplementedException('Write');
    }

    /**
     * Returns FALSE if this node is currently in the checked-in state (either
     * due to its own status as a versionable node or due to the effect of
     * a versionable node being checked in above it). Otherwise this method
     * returns TRUE. This includes the case where the repository does not
     * support versioning (and therefore all nodes are always "checked-out",
     * by default).
     *
     * @return boolean
     * @throws PHPCR_RepositoryException if an error occurs.
     * @api
     */
    public function isCheckedOut() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns TRUE if this node is locked either as a result of a lock held
     * by this node or by a deep lock on a node above this node;
     * otherwise returns FALSE. This includes the case where a repository does
     * not support locking (in which case all nodes are "unlocked" by default).
     *
     * @return boolean.
     * @throws PHPCR_RepositoryException if an error occurs.
     * @api
     */
    public function isLocked() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Causes the lifecycle state of this node to undergo the specified transition.
     * This method may change the value of the jcr:currentLifecycleState property,
     * in most cases it is expected that the implementation will change the value
     * to that of the passed transition parameter, though this is an
     * implementation-specific issue. If the jcr:currentLifecycleState property
     * is changed the change is persisted immediately, there is no need to call
     * save.
     *
     * @param string $transition a state transition
     * @return void
     * @throws PHPCR_UnsupportedRepositoryOperationException  if this implementation does not support lifecycle actions or if this node does not have the mix:lifecycle mixin.
     * @throws PHPCR_InvalidLifecycleTransitionException if the lifecycle transition is not successful.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function followLifecycleTransition($transition) {
        throw new jackalope_NotImplementedException('Write');
    }

    /**
     * Returns the list of valid state transitions for this node.
     *
     * @return array a string array.
     * @throws PHPCR_UnsupportedRepositoryOperationException  if this implementation does not support lifecycle actions or if this node does not have the mix:lifecycle mixin.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function getAllowedLifecycleTransitions() {
        throw new jackalope_NotImplementedException('Write');
    }

    /** filter the list of names according to the filter expression / array
     * @param string|array $filter according to getNodes|getProperties
     * @param array $names list of names to filter
     * @return the names in $names that match a filter
     */
    protected static function filterNames($filter, $names) {
        if (is_string($filter)) {
            $filter = explode('|', $filter);
        }
        $filtered = array();
        if ($filter !== null) {
            foreach($filter as $k => $f) {
               $f = trim($f);
               $filter[$k] = strtr($f, array('*'=>'.*', //wildcard
                                             '.'  => '\\.', //escape regexp
                                             '\\' => '\\\\',
                                             '{'  => '\\{',
                                             '}'  => '\\}',
                                             '('  => '\\(',
                                             ')'  => '\\)',
                                             '+'  => '\\+',
                                             '^'  => '\\^',
                                             '$'  => '\\$'));
            }
            foreach($names as $name) {
                foreach($filter as $f) {
                    if (preg_match('/^'.$f.'$/', $name)) {
                        $filtered[] = $name;
                    }
                }
            }
        } else {
            $filtered = $names;
        }
        return $filtered;
    }
}
