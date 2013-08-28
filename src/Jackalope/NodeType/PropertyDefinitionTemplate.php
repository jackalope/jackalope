<?php
namespace Jackalope\NodeType;

use PHPCR\PropertyType;

use PHPCR\Version\OnParentVersionAction;
use PHPCR\NodeType\PropertyDefinitionTemplateInterface;

use Jackalope\FactoryInterface;

/**
 * {@inheritDoc}
 *
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
     * @param FactoryInterface $factory         the object factory
     * @param NodeTypeManager  $nodeTypeManager
     */
    public function __construct(FactoryInterface $factory, NodeTypeManager $nodeTypeManager)
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

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setAutoCreated($autoCreated)
    {
        $this->isAutoCreated = $autoCreated;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setMandatory($mandatory)
    {
        $this->isMandatory = $mandatory;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setOnParentVersion($opv)
    {
        $this->onParentVersion = $opv;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setProtected($protectedStatus)
    {
        $this->isProtected = $protectedStatus;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setRequiredType($type)
    {
        $this->requiredType = $type;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setValueConstraints(array $constraints)
    {
        $this->valueConstraints = $constraints;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setDefaultValues(array $defaultValues)
    {
        $this->defaultValues = $defaultValues;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setMultiple($multiple)
    {
        $this->isMultiple = $multiple;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setAvailableQueryOperators(array $operators)
    {
        $this->availableQueryOperators = $operators;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setFullTextSearchable($fullTextSearchable)
    {
        $this->isFullTextSearchable = $fullTextSearchable;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setQueryOrderable($queryOrderable)
    {
        $this->isQueryOrderable = $queryOrderable;
    }
}
