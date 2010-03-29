<?php
/**
 * A NodeType object represents a "live" node type that is registered in the repository.
 */
class jackalope_NodeType_NodeType extends jackalope_NodeType_NodeTypeDefinition implements PHPCR_NodeType_NodeTypeInterface {
    
    /**
     * Initializes the NodeTypeDefinition from the given DOM
     * @param DOMElement NodeTypeElement
     */
    public function __construct(DOMElement $node, jackalope_NodeType_NodeTypeManager $nodeTypeManager) {
        parent::__construct($node, $nodeTypeManager);
    }
    
    /**
     * Returns all supertypes of this node type in the node type inheritance
     * hierarchy. For primary types apart from nt:base, this list will always
     * include at least nt:base. For mixin types, there is no required supertype.
     *
     * @return array of PHPCR_NodeType_NodeType objects.
     * @api
     */
    public function getSupertypes() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns the direct supertypes of this node type in the node type
     * inheritance hierarchy, that is, those actually declared in this node
     * type. In single-inheritance systems, this will always be an array of
     * size 0 or 1. In systems that support multiple inheritance of node
     * types this array may be of size greater than 1.
     *
     * @return array of PHPCR_NodeType_NodeType objects.
     * @api
     */
    public function getDeclaredSupertypes() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns all subtypes of this node type in the node type inheritance
     * hierarchy.
     *
     * @see getDeclaredSubtypes()
     *
     * @return PHPCR_NodeType_NodeTypeIteratorInterface a NodeTypeIterator.
     * @api
     */
    public function getSubtypes() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns the direct subtypes of this node type in the node type inheritance
     * hierarchy, that is, those which actually declared this node type in their
     * list of supertypes.
     *
     * @see getSubtypes()
     *
     * @return PHPCR_NodeType_NodeTypeIteratorInterface a NodeTypeIterator.
     * @api
     */
    public function getDeclaredSubtypes() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns true if the name of this node type or any of its direct or
     * indirect supertypes is equal to nodeTypeName, otherwise returns false.
     *
     * @param string $nodeTypeName the name of a node type.
     * @return boolean
     * @api
     */
    public function isNodeType($nodeTypeName) {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns an array containing the property definitions of this node
     * type. This includes both those property definitions actually declared
     * in this node type and those inherited from the supertypes of this type.
     *
     * @return array an array of PHPCR_NodeType_PropertyDefinition containing the property definitions.
     * @api
     */
    public function getPropertyDefinitions() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns an array containing the child node definitions of this node type.
     * This includes both those child node definitions actually declared in this
     * node type and those inherited from the supertypes of this node type.
     *
     * @return array an array of PHPCR_NodeType_NodeDefinition containing the child node definitions.
     * @api
     */
    public function getChildNodeDefinitions() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns true if setting propertyName to value is allowed by this node type.
     * Otherwise returns false.
     *
     * @param string $propertyName The name of the property
     * @param PHPCR_ValueInterface|array $value A PHPCR_ValueInterface object or an array of PHPCR_ValueInterface objects.
     * @return boolean
     * @api
     */
    public function canSetProperty($propertyName, $value) {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns true if this node type allows the addition of a child node called
     * childNodeName without specific node type information (that is, given the
     * definition of this parent node type, the child node name is sufficient to
     * determine the intended child node type). Returns false otherwise.
     * If $nodeTypeName is given returns true if this node type allows the
     * addition of a child node called childNodeName of node type nodeTypeName.
     * Returns false otherwise.
     *
     * @param string $childNodeName The name of the child node.
     * @param string $nodeTypeName The name of the node type of the child node.
     * @return boolean
     * @api
     */
    public function canAddChildNode($childNodeName, $nodeTypeName = NULL) {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns true if removing the child node called nodeName is allowed by this
     * node type. Returns false otherwise.
     *
     * @param string $nodeName The name of the child node
     * @return boolean
     * @api
     */
    public function canRemoveNode($nodeName) {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns true if removing the property called propertyName is allowed by this
     * node type. Returns false otherwise.
     *
     * @param string $propertyName The name of the property
     * @return boolean
     * @api
     */
    public function canRemoveProperty($propertyName) {
        throw new jackalope_NotImplementedException();
    }
}
