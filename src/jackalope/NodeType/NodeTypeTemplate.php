<?php

/**
 * The NodeTypeTemplate interface represents a simple container structure used
 * to define node types which are then registered through the
 * NodeTypeManager.registerNodeType method.
 *
 * NodeTypeTemplate, like NodeType, is a subclass of NodeTypeDefinition so it
 * shares with NodeType those methods that are relevant to a static definition.
 * In addition, NodeTypeTemplate provides methods for setting the attributes of
 * the definition. Implementations of this interface need not contain any
 * validation logic.
 *
 * See the corresponding get methods for each attribute in NodeTypeDefinition
 * for the default values assumed when a new empty NodeTypeTemplate is created
 * (as opposed to one extracted from an existing NodeType).
 */
class jackalope_NodeType_NodeTypeTemplate extends jackalope_NodeType_NodeTypeDefinition implements PHPCR_NodeType_NodeTypeTemplateInterface {


    /**
     * Sets the name of the node type.
     *
     * @param string $name a String.
     * @return void
     * @api
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * Sets the names of the supertypes of the node type.
     *
     * @param array $names a String array.
     * @return void
     * @api
     */
    public function setDeclaredSuperTypeNames(array $names) {
        $this->declaredSuperTypeNames = $names;
    }

    /**
     * Sets the abstract flag of the node type.
     *
     * @param boolean $abstractStatus a boolean.
     * @return void
     * @api
     */
    public function setAbstract($abstractStatus) {
        $this->isAbstract = $abstractStatus;
    }

    /**
     * Sets the mixin flag of the node type.
     *
     * @param boolean $mixin a boolean.
     * @return void
     * @api
     */
    public function setMixin($mixin) {
        $this->isMixin = $mixin;
    }

    /**
     * Sets the orderable child nodes flag of the node type.
     *
     * @param boolean $orderable a boolean.
     * @return void
     * @api
     */
    public function setOrderableChildNodes($orderable) {
        $this->hasOrderableChildNodes = $orderable;
    }

    /**
     * Sets the name of the primary item.
     *
     * @param string $name a String.
     * @return void
     * @api
     */
    public function setPrimaryItemName($name) {
        $this->primaryItemName = $name;
    }

    /**
     * Sets the queryable status of the node type.
     *
     * @param booolean $queryable a boolean.
     * @return void
     * @api
     */
    public function setQueryable($queryable) {
        $this->isQueryable = $queryable;
    }

    /**
     * Returns a mutable List of PropertyDefinitionTemplate objects. To define a
     * new NodeTypeTemplate or change an existing one, PropertyDefinitionTemplate
     * objects can be added to or removed from this List.
     *
     * @return array a mutable List of PropertyDefinitionTemplate objects.
     * @api
     */
    public function getPropertyDefinitionTemplates() {
        return $this->declaredPropertyDefinitions;
    }

    /**
     * Returns a mutable List of NodeDefinitionTemplate objects. To define a new
     * NodeTypeTemplate or change an existing one, NodeDefinitionTemplate objects
     * can be added to or removed from this List.
     *
     * @return array a mutable List of NodeDefinitionTemplate objects.
     * @api
     */
    public function getNodeDefinitionTemplates() {
        return $this->declaredNodeDefinitions;
    }

}


