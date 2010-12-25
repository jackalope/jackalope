<?php
namespace Jackalope\NodeType;

/**
 * The PropertyDefinitionTemplate interface extends PropertyDefinition with the
 * addition of write methods, enabling the characteristics of a child property
 * definition to be set, after which the PropertyDefinitionTemplate is added to
 * a NodeTypeTemplate.
 *
 * See the corresponding get methods for each attribute in PropertyDefinition for
 * the default values assumed when a new empty PropertyDefinitionTemplate is created
 * (as opposed to one extracted from an existing NodeType).
 */
class PropertyDefinitionTemplate extends PropertyDefinition implements \PHPCR\NodeType\PropertyDefinitionTemplateInterface
{
    public function __construct($factory, NodeTypeManager $nodeTypeManager)
    {
        $this->factory = $factory;
        $this->nodeTypeManager = $nodeTypeManager;

        // initialize empty values
        $this->requiredType = \PHPCR\PropertyType::STRING;
        $this->valueConstraints = null;
        $this->defaultValues = null;
        $this->isMultiple = false;
        //$this->availableQueryOperators = ?
        $this->isFullTextSearchable = false;
        $this->isQueryOrderable = false;
        $this->name = null;
        $this->isAutoCreated = false;
        $this->isMandatory = false;
        $this->onParentVersion = \PHPCR\Version\OnParentVersionAction::COPY;
        $this->isProtected = false;
    }

    /**
     * Sets the name of the property.
     *
     * @param string $name a String.
     * @return void
     * @api
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Sets the auto-create status of the property.
     *
     * @param boolean $autoCreated a boolean.
     * @return void
     * @api
     */
    public function setAutoCreated($autoCreated)
    {
        $this->isAutoCreated = $autoCreated;
    }

    /**
     * Sets the mandatory status of the property.
     *
     * @param boolean $mandatory a boolean.
     * @return void
     * @api
     */
    public function setMandatory($mandatory)
    {
        $this->isMandatory = $mandatory;
    }

    /**
     * Sets the on-parent-version status of the property.
     *
     * @param integer $opv an int constant member of OnParentVersionAction.
     * @return void
     * @api
     */
    public function setOnParentVersion($opv)
    {
        $this->onParentVersion = $opv;
    }

    /**
     * Sets the protected status of the property.
     *
     * @param boolean $protectedStatus a boolean.
     * @return void
     * @api
     */
    public function setProtected($protectedStatus)
    {
        $this->isProtected;
    }

    /**
     * Sets the required type of the property.
     *
     * @param integer $type an int constant member of PropertyType.
     * @return void
     * @api
     */
    public function setRequiredType($type)
    {
        $this->requiredType = $type;
    }

    /**
     * Sets the value constraints of the property.
     *
     * @param array $constraints a String array.
     * @return void
     * @api
     */
    public function setValueConstraints(array $constraints)
    {
        $this->valueConstraints = $constraints;
    }


    /**
     * Sets the default value (or values, in the case of a multi-value property)
     * of the property.
     *
     * @param array $defaultValues a Value array.
     * @return void
     * @api
     */
    public function setDefaultValues(array $defaultValues)
    {
        $this->defaultValues = $defaultValues;
    }

    /**
     * Sets the multi-value status of the property.
     *
     * @param boolean $multiple a boolean.
     * @return void
     * @api
     */
    public function setMultiple($multiple)
    {
        $this->isMultiple = $multiple;
    }

    /**
     * Sets the queryable status of the property.
     *
     * @param array operators an array of String constants. See PropertyDefinition#getAvailableQueryOperators().
     * @return void
     * @api
     */
    public function setAvailableQueryOperators(array $operators)
    {
        $this->availableQueryOperators = $operators;
    }

    /**
     * Sets the full-text-searchable status of the property.
     *
     * @param boolean $fullTextSearchable a boolean.
     * @return void
     * @api
     */
    public function setFullTextSearchable($fullTextSearchable)
    {
        $this->isFullTextSearchable = $fullTextSearchable;
    }

    /**
     * Sets the query-orderable status of the property.
     *
     * @param boolean $queryOrderable a boolean.
     * @return void
     * @api
     */
    public function setQueryOrderable($queryOrderable)
    {
        $this->isQueryOrderable = $queryOrderable;
    }

}
