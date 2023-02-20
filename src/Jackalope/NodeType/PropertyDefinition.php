<?php

namespace Jackalope\NodeType;

use PHPCR\NodeType\PropertyDefinitionInterface;
use PHPCR\PropertyType;

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
     * One of the PropertyType type constants.
     *
     * @see PropertyType
     */
    protected int $requiredType;

    /**
     * @var string[]
     */
    protected array $valueConstraints;

    /**
     * @var mixed
     */
    protected $defaultValues;

    protected bool $isMultiple = false;

    /**
     * List of constant values.
     *
     * @var string[]
     *
     * @see QueryObjectModelConstantsInterface
     */
    protected array $availableQueryOperators = [];

    protected bool $isFullTextSearchable = false;

    protected bool $isQueryOrderable = false;

    /**
     * Treat more information in addition to ItemDefinition::fromArray().
     *
     * See class documentation for the fields supported in the array.
     *
     * @param array{"declaringNodeType": string, "name": string, "isAutoCreated": boolean, "isMandatory": boolean, "isProtected": boolean, "onParentVersion": int, "requiredType": int, "multiple": boolean, "fullTextSearchable": boolean, "queryOrderable": boolean, "valueConstraints": string[], "availableQueryOperators": string[], "defaultValues": mixed} $data
     */
    protected function fromArray(array $data): void
    {
        parent::fromArray($data);
        $this->requiredType = $data['requiredType'];
        $this->isMultiple = $data['multiple'] ?? false;
        $this->isFullTextSearchable = $data['fullTextSearchable'] ?? false;
        $this->isQueryOrderable = $data['queryOrderable'] ?? false;
        $this->valueConstraints = $data['valueConstraints'] ?? [];
        $this->availableQueryOperators = $data['availableQueryOperators'] ?? [];
        $this->defaultValues = $data['defaultValues'] ?? [];
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getRequiredType(): int
    {
        return $this->requiredType;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getValueConstraints(): array
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
    public function isMultiple(): bool
    {
        return $this->isMultiple;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAvailableQueryOperators(): array
    {
        return $this->availableQueryOperators;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isFullTextSearchable(): bool
    {
        return $this->isFullTextSearchable;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isQueryOrderable(): bool
    {
        return $this->isQueryOrderable;
    }
}
