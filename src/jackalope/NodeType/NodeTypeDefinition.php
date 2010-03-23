<?php

class jackalope_NodeType_NodeTypeDefinition implements PHPCR_NodeType_NodeTypeDefinitionInterface {
    /**
     * Returns the name of the node type.
     * In implementations that support node type registration, if this
     * NodeTypeDefinition object is actually a newly-created empty
     * NodeTypeTemplate, then this method will return null.
     *
     * @return string a String
     * @api
     */
    public function getName() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns the names of the supertypes actually declared in this node type.
     * In implementations that support node type registration, if this
     * NodeTypeDefinition object is actually a newly-created empty
     * NodeTypeTemplate, then this method will return an array containing a
     * single string indicating the node type nt:base.
     *
     * @return array an array of Strings
     * @api
     */
    public function getDeclaredSupertypeNames() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns true if this is an abstract node type; returns false otherwise.
     * An abstract node type is one that cannot be assigned as the primary or
     * mixin type of a node but can be used in the definitions of other node
     * types as a superclass.
     *
     * In implementations that support node type registration, if this
     * NodeTypeDefinition object is actually a newly-created empty
     * NodeTypeTemplate, then this method will return false.
     *
     * @return boolean a boolean
     * @api
     */
    public function isAbstract() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns true if this is a mixin type; returns false if it is primary.
     * In implementations that support node type registration, if this
     * NodeTypeDefinition object is actually a newly-created empty
     * NodeTypeTemplate, then this method will return false.
     *
     * @return boolean a boolean
     * @api
     */
    public function isMixin() {
        throw new jackalope_NotImplementedException();
    }

    /*
     * Returns true if nodes of this type must support orderable child nodes;
     * returns false otherwise. If a node type returns true on a call to this
     * method, then all nodes of that node type must support the method
     * Node.orderBefore. If a node type returns false on a call to this method,
     * then nodes of that node type may support Node.orderBefore. Only the primary
     * node type of a node controls that node's status in this regard. This setting
     * on a mixin node type will not have any effect on the node.
     * In implementations that support node type registration, if this
     * NodeTypeDefinition object is actually a newly-created empty
     * NodeTypeTemplate, then this method will return false.
     *
     * @return boolean a boolean
     * @api
     */
    public function hasOrderableChildNodes() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns TRUE if the node type is queryable, meaning that the
     * available-query-operators, full-text-searchable and query-orderable
     * attributes of its property definitions take effect. See
     * PropertyDefinition#getAvailableQueryOperators(),
     * PropertyDefinition#isFullTextSearchable() and
     * PropertyDefinition#isQueryOrderable().
     *
     * If a node type is declared non-queryable then these attributes of its
     * property definitions have no effect.
     *
     * @return boolean a boolean
     * @api
     */
    public function isQueryable() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns the name of the primary item (one of the child items of the nodes
     * of this node type). If this node has no primary item, then this method
     * returns null. This indicator is used by the method Node.getPrimaryItem().
     * In implementations that support node type registration, if this
     * NodeTypeDefinition object is actually a newly-created empty
     * NodeTypeTemplate, then this method will return null.
     *
     * @return string a String
     * @api
     */
    public function getPrimaryItemName() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns an array containing the property definitions actually declared
     * in this node type.
     * In implementations that support node type registration, if this
     * NodeTypeDefinition object is actually a newly-created empty
     * NodeTypeTemplate, then this method will return null.
     *
     * @return array an array of PropertyDefinitions
     * @api
     */
    public function getDeclaredPropertyDefinitions() {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns an array containing the child node definitions actually
     * declared in this node type.
     * In implementations that support node type registration, if this
     * NodeTypeDefinition object is actually a newly-created empty
     * NodeTypeTemplate, then this method will return null.
     *
     * @return array an array of NodeDefinitions
     * @api
     */
    public function getDeclaredChildNodeDefinitions() {
        throw new jackalope_NotImplementedException();
    }
}
