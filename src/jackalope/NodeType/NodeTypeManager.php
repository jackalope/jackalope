<?php

/**
 * Allows for the retrieval and (in implementations that support it) the
 * registration of node types. Accessed via Workspace.getNodeTypeManager().
 */
class jackalope_NodeType_NodeTypeManager implements PHPCR_NodeType_NodeTypeManagerInterface {
    protected $objectManager;
    
    protected $primaryTypes;
    protected $mixinTypes;
    
    public function __construct(jackalope_ObjectManager $objectManager) {
        $this->objectManager = $objectManager;
    }
    
    /**
     * Creates NodeTypes from the given dom
     * @param DOMDocument nodetypes dom from jackrabbit
     * @return void
     */
    protected function createNodeTypes($dom) {
        $xp = new DOMXpath($dom);
        $nodetypes = $xp->query('/nodeTypes/nodeType');
        foreach ($nodetypes as $nodetype) {
            $nodetype = jackalope_Factory::get('NodeType_NodeType', array($nodetype, $this));
            if ($nodetype->isMixin()) {
                $this->mixinTypes[$nodetype->getName()] = $nodetype;
            } else {
                $this->primaryTypes[$nodetype->getName()] = $nodetype;
            }
        }
    }
    
    /**
     * Returns the named node type.
     *
     * @param string $nodeTypeName the name of an existing node type.
     * @return PHPCR_NodeType_NodeTypeInterface A NodeType object.
     * @throws PHPCR_NodeType_NoSuchNodeTypeException if no node type by the given name exists.
     * @throws PHPCR_RepositoryException if another error occurs.
     */
    public function getNodeType($nodeTypeName) {
        if (empty($this->primaryTypes[$nodeTypeName]) && empty($this->mixinTypes[$nodeTypeName])) {
            $this->createNodeTypes($this->objectManager->getNodeType($nodeTypeName));
        }
        
        if (isset($this->primaryTypes[$nodeTypeName])) {
            return $this->primaryTypes[$nodeTypeName];
        } elseif (isset($this->mixinTypes[$nodeTypeName])) {
            return $this->mixinTypes[$nodeTypeName];
        } else {
            throw new PHPCR_NodeType_NoSuchNodeTypeException($nodeTypeName);
        }
    }

    /**
     * Returns true if a node type with the specified name is registered. Returns
     * false otherwise.
     *
     * @param string $name - a String.
     * @return boolean a boolean
     * @throws PHPCR_RepositoryException if an error occurs.
     */
    public function hasNodeType($name) {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns an iterator over all available node types (primary and mixin).
     *
     * @return PHPCR_NodeType_NodeTypeInteratorInterface An NodeTypeIterator.
     * @throws PHPCR_RepositoryException if an error occurs.
     */
    public function getAllNodeTypes() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns an iterator over all available primary node types.
     *
     * @return PHPCR_NodeType_NodeTypeIteratorInterface An NodeTypeIterator.
     * @throws PHPCR_RepositoryException if an error occurs.
     */
    public function getPrimaryNodeTypes() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns an iterator over all available mixin node types. If none are available,
     * an empty iterator is returned.
     *
     * @return PHPCR_NodeType_NodeTypeIteratorInterface An NodeTypeIterator.
     * @throws PHPCR_RepositoryException if an error occurs.
     */
    public function getMixinNodeTypes() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns an empty NodeTypeTemplate which can then be used to define a node type
     * and passed to NodeTypeManager.registerNodeType.
     *
     * If $ntd is given:
     * Returns a NodeTypeTemplate holding the specified node type definition. This
     * template can then be altered and passed to NodeTypeManager.registerNodeType.
     *
     * @param PHPCR_NodeType_NodeTypeDefinitionInterface $ntd a NodeTypeDefinition.
     * @return PHPCR_NodeType_NodeTypeTemplateInterface A NodeTypeTemplate.
     * @throws PHPCR_UnsupportedRepositoryOperationException if this implementation does not support node type registration.
     * @throws PHPCR_RepositoryException if another error occurs.
     */
    public function createNodeTypeTemplate($ntd = NULL) {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns an empty NodeDefinitionTemplate which can then be used to create a
     * child node definition and attached to a NodeTypeTemplate.
     *
     * @return PHPCR_NodeType_NodeDefinitionTemplateInterface A NodeDefinitionTemplate.
     * @throws PHPCR_UnsupportedRepositoryOperationException if this implementation does not support node type registration.
     * @throws PHPCR_RepositoryException if another error occurs.
     */
    public function createNodeDefinitionTemplate() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns an empty PropertyDefinitionTemplate which can then be used to create
     * a property definition and attached to a NodeTypeTemplate.
     *
     * @return PHPCR_NodeType_PropertyDefinitionTemplateInterface A PropertyDefinitionTemplate.
     * @throws PHPCR_UnsupportedRepositoryOperationException if this implementation does not support node type registration.
     * @throws PHPCR_RepositoryException if another error occurs.
     */
    public function createPropertyDefinitionTemplate() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Registers a new node type or updates an existing node type using the specified
     * definition and returns the resulting NodeType object.
     * Typically, the object passed to this method will be a NodeTypeTemplate (a
     * subclass of NodeTypeDefinition) acquired from NodeTypeManager.createNodeTypeTemplate
     * and then filled-in with definition information.
     *
     * @param PHPCR_NodeType_NodeTypeDefinitionInterface $ntd an NodeTypeDefinition.
     * @param boolean $allowUpdate a boolean
     * @return PHPCR_NodeType_NodeTypeInterface the registered node type
     * @throws PHPCR_InvalidNodeTypeDefinitionException if the NodeTypeDefinition is invalid.
     * @throws PHPCR_NodeType_NodeTypeExistsException if allowUpdate is false and the NodeTypeDefinition specifies a node type name that is already registered.
     * @throws PHPCR_UnsupportedRepositoryOperationException if this implementation does not support node type registration.
     * @throws PHPCR_RepositoryException if another error occurs.
     */
    public function registerNodeType(PHPCR_NodeType_NodeTypeDefinitionInterface $ntd, $allowUpdate) {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Registers or updates the specified array of NodeTypeDefinition objects.
     * This method is used to register or update a set of node types with mutual
     * dependencies. Returns an iterator over the resulting NodeType objects.
     * The effect of the method is "all or nothing"; if an error occurs, no node
     * types are registered or updated.
     *
     * @param array $definitions an array of NodeTypeDefinitions
     * @param boolean $allowUpdate a boolean
     * @return PHPCR_NodeType_NodeTypeIteratorInterface the registered node types.
     * @throws PHPCR_InvalidNodeTypeDefinitionException - if a NodeTypeDefinition within the Collection is invalid or if the Collection contains an object of a type other than NodeTypeDefinition.
     * @throws PHPCR_NodeType_NodeTypeExistsException if allowUpdate is false and a NodeTypeDefinition within the Collection specifies a node type name that is already registered.
     * @throws PHPCR_UnsupportedRepositoryOperationException if this implementation does not support node type registration.
     * @throws PHPCR_RepositoryException if another error occurs.
     */
    public function registerNodeTypes(array $definitions, $allowUpdate) {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Unregisters the specified node type.
     *
     * @param string $name a String.
     * @return void
     * @throws PHPCR_UnsupportedRepositoryOperationException if this implementation does not support node type registration.
     * @throws PHPCR_NodeType_NoSuchNodeTypeException if no registered node type exists with the specified name.
     * @throws PHPCR_RepositoryException if another error occurs.
     */
    public function unregisterNodeType($name) {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Unregisters the specified set of node types. Used to unregister a set of node
     * types with mutual dependencies.
     *
     * @param array $names a String array
     * @return void
     * @throws PHPCR_UnsupportedRepositoryOperationException if this implementation does not support node type registration.
     * @throws PHPCR_NodeType_NoSuchNodeTypeException if one of the names listed is not a registered node type.
     * @throws PHPCR_RepositoryException if another error occurs.
     */
    public function unregisterNodeTypes(array $names) {
        throw new jackalope_NotImplementedException();
    }
}
