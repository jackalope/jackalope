<?php
namespace Jackalope\NodeType;

use PHPCR\PropertyType;

use PHPCR\Version\OnParentVersionAction;
use PHPCR\NodeType\PropertyDefinitionTemplateInterface;

// inherit all doc
/**
 * @api
 */
class PropertyDefinitionTemplate extends PropertyDefinition implements PropertyDefinitionTemplateInterface
{
    /**
     * Create a new property definition template.
     *
     * @param object $factory an object factory implementing "get" as
     *      described in \Jackalope\Factory
     * @param NodeTypeManager $nodeTypeManager
     */
    public function __construct($factory, NodeTypeManager $nodeTypeManager)
    {
        $this->factory = $factory;
        $this->nodeTypeManager = $nodeTypeManager;

        // initialize empty values
        $this->requiredType = PropertyType::STRING;
        $this->valueConstraints = null;
        $this->defaultValues = null;
        $this->isMultiple = false;
        //$this->availableQueryOperators = ?
        $this->isFullTextSearchable = false;
        $this->isQueryOrderable = false;
        $this->name = null;
        $this->isAutoCreated = false;
        $this->isMandatory = false;
        $this->onParentVersion = OnParentVersionAction::COPY;
        $this->isProtected = false;
    }

    // inherit all doc
    /**
     * @api
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    // inherit all doc
    /**
     * @api
     */
    public function setAutoCreated($autoCreated)
    {
        $this->isAutoCreated = $autoCreated;
    }

    // inherit all doc
    /**
     * @api
     */
    public function setMandatory($mandatory)
    {
        $this->isMandatory = $mandatory;
    }

    // inherit all doc
    /**
     * @api
     */
    public function setOnParentVersion($opv)
    {
        $this->onParentVersion = $opv;
    }

    // inherit all doc
    /**
     * @api
     */
    public function setProtected($protectedStatus)
    {
        $this->isProtected;
    }

    // inherit all doc
    /**
     * @api
     */
    public function setRequiredType($type)
    {
        $this->requiredType = $type;
    }

    // inherit all doc
    /**
     * @api
     */
    public function setValueConstraints(array $constraints)
    {
        $this->valueConstraints = $constraints;
    }


    // inherit all doc
    /**
     * @api
     */
    public function setDefaultValues(array $defaultValues)
    {
        $this->defaultValues = $defaultValues;
    }

    // inherit all doc
    /**
     * @api
     */
    public function setMultiple($multiple)
    {
        $this->isMultiple = $multiple;
    }

    // inherit all doc
    /**
     * @api
     */
    public function setAvailableQueryOperators(array $operators)
    {
        $this->availableQueryOperators = $operators;
    }

    // inherit all doc
    /**
     * @api
     */
    public function setFullTextSearchable($fullTextSearchable)
    {
        $this->isFullTextSearchable = $fullTextSearchable;
    }

    // inherit all doc
    /**
     * @api
     */
    public function setQueryOrderable($queryOrderable)
    {
        $this->isQueryOrderable = $queryOrderable;
    }

}
