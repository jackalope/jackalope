<?php

namespace Jackalope\NodeType;

use Jackalope\Version\VersionHandler;
use PHPCR\NodeType\PropertyDefinitionInterface;
use PHPCR\RepositoryException;

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

    /**
     * Returns the default value for properties with this PropertyDefinition.
     *
     * @internal
     */
    public function determineDefaultValue()
    {
        $nodeType = $this->getDeclaringNodeType();

        if ($this->isMultiple()) {
            $value = $this->defaultValues;
        } elseif (isset($this->defaultValues[0])) {
            $value = $this->defaultValues[0];
        } elseif ($nodeType->getName() !== VersionHandler::MIX_VERSIONABLE) {
            // When implementing versionable or activity, we need to handle more properties explicitly
            throw new RepositoryException(sprintf(
                'No default value for autocreated property "%s" for node type "%s"',
                $this->getName(),
                $nodeType->getName()
            ));
        }

        return $value;
    }
}
