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
    protected $valueConstraints = [];

    /**
     * @var mixed
     */
    protected $defaultValues = [];

    /**
     * @var boolean
     */
    protected $isMultiple;

    /**
     * List of constants from \PHPCR\Query\QueryObjectModelConstantsInterface
     *
     * @var array
     */
    protected $availableQueryOperators = [];

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
        $this->valueConstraints = isset($data['valueConstraints']) ? $data['valueConstraints'] : [];
        $this->availableQueryOperators = isset($data['availableQueryOperators']) ? $data['availableQueryOperators'] : [];
        $this->defaultValues = isset($data['defaultValues']) ? $data['defaultValues'] : [];
    }

    /**
     * {@inheritDoc}
     *
     * @return int an integer constant member of PropertyType
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
     * @return string[]
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
     * @return array<mixed>
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
     * @return bool true, if this property may have multiple values, else
     *              false
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
     * @return string[] query operator constants as defined in \PHPCR\Query\QueryObjectModelConstantsInterface
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
     * @return bool true, if this property is full-text searchable, else false
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
     * @return bool true, if this property is query orderable, else false
     *
     * @api
     */
    public function isQueryOrderable()
    {
        return $this->isQueryOrderable;
    }
}
