<?php

namespace Jackalope\NodeType;

use Jackalope\FactoryInterface;
use PHPCR\NodeType\PropertyDefinitionTemplateInterface;
use PHPCR\PropertyType;
use PHPCR\Version\OnParentVersionAction;

/**
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
class PropertyDefinitionTemplate extends PropertyDefinition implements PropertyDefinitionTemplateInterface
{
    /**
     * Create a new property definition template.
     *
     * @param FactoryInterface $factory the object factory
     */
    public function __construct(FactoryInterface $factory, NodeTypeManager $nodeTypeManager)
    {
        $this->factory = $factory;
        $this->nodeTypeManager = $nodeTypeManager;

        // initialize empty values
        $this->requiredType = PropertyType::STRING;
        $this->valueConstraints = [];
        $this->defaultValues = [];
        $this->isMultiple = false;
        // $this->availableQueryOperators = ?
        $this->isFullTextSearchable = false;
        $this->isQueryOrderable = false;
        $this->name = null;
        $this->isAutoCreated = false;
        $this->isMandatory = false;
        $this->onParentVersion = OnParentVersionAction::COPY;
        $this->isProtected = false;
    }

    /**
     * @api
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @api
     */
    public function setAutoCreated($autoCreated): void
    {
        $this->isAutoCreated = $autoCreated;
    }

    /**
     * @api
     */
    public function setMandatory($mandatory): void
    {
        $this->isMandatory = $mandatory;
    }

    /**
     * @api
     */
    public function setOnParentVersion($opv): void
    {
        $this->onParentVersion = $opv;
    }

    /**
     * @api
     */
    public function setProtected($protectedStatus): void
    {
        $this->isProtected = $protectedStatus;
    }

    /**
     * @api
     */
    public function setRequiredType($type): void
    {
        $this->requiredType = $type;
    }

    /**
     * @api
     */
    public function setValueConstraints(array $constraints): void
    {
        $this->valueConstraints = $constraints;
    }

    /**
     * @api
     */
    public function setDefaultValues(array $defaultValues): void
    {
        $this->defaultValues = $defaultValues;
    }

    /**
     * @api
     */
    public function setMultiple($multiple): void
    {
        $this->isMultiple = $multiple;
    }

    /**
     * @api
     */
    public function setAvailableQueryOperators(array $operators): void
    {
        $this->availableQueryOperators = $operators;
    }

    /**
     * @api
     */
    public function setFullTextSearchable($fullTextSearchable): void
    {
        $this->isFullTextSearchable = $fullTextSearchable;
    }

    /**
     * @api
     */
    public function setQueryOrderable($queryOrderable): void
    {
        $this->isQueryOrderable = $queryOrderable;
    }
}
