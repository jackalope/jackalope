<?php

namespace Jackalope\NodeType;

use PHPCR\NodeType\PropertyDefinitionInterface;

/**
 * {@inheritDoc}
 *
 * TODO: document array format of constructor
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
class PropertyDefinition extends ItemDefinition implements PropertyDefinitionInterface
{
    /**
     * One of the PropertyType type constants
     * @var int
     */
    protected $requiredType;
    /**
     * The constraint information array (array of strings)
     * @var array
     */
    protected $valueConstraints = array();
    /**
     * @var mixed
     */
    protected $defaultValues = array();
    /**
     * @var boolean
     */
    protected $isMultiple;
    /**
     * List of constants from \PHPCR\Query\QueryObjectModelConstantsInterface
     * @var array
     */
    protected $availableQueryOperators = array();
    /**
     * @var boolean
     */
    protected $isFullTextSearchable;
    /**
     * @var boolean
     */
    protected $isQueryOrderable;

    /**
     * Treat more information in addition to ItemDefinition::fromArray()
     *
     * See class documentation for the fields supported in the array.
     *
     * @param array $data The property definition in array form.
     */
    protected function fromArray(array $data)
    {
        parent::fromArray($data);
        $this->requiredType = $data['requiredType'];
        $this->isMultiple = isset($data['multiple']) ? $data['multiple'] : false;
        $this->isFullTextSearchable = isset($data['fullTextSearchable']) ? $data['fullTextSearchable'] : false;
        $this->isQueryOrderable = isset($data['queryOrderable']) ? $data['queryOrderable'] : false;
        $this->valueConstraints = isset($data['valueConstraints']) ? $data['valueConstraints'] : array();
        $this->availableQueryOperators = isset($data['availableQueryOperators']) ? $data['availableQueryOperators'] : array();
        $this->defaultValues = isset($data['defaultValues']) ? $data['defaultValues'] : array();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getRequiredType()
    {
        return $this->requiredType;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getValueConstraints()
    {
        return $this->valueConstraints;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDefaultValues()
    {
        return $this->defaultValues;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isMultiple()
    {
        return $this->isMultiple;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAvailableQueryOperators()
    {
        return $this->availableQueryOperators;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isFullTextSearchable()
    {
        return $this->isFullTextSearchable;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isQueryOrderable()
    {
        return $this->isQueryOrderable;
    }
}
