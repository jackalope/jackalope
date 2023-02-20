<?php

namespace Jackalope\NodeType;

use PHPCR\NodeType\NodeDefinitionInterface;

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
class NodeDefinition extends ItemDefinition implements NodeDefinitionInterface
{
    private const DEFAULT_PRIMARY_NODE = 'nt:base';

    /**
     * Cached list of NodeType instances populated in first call to getRequiredPrimaryTypes.
     */
    protected array $requiredPrimaryTypes = [];

    /**
     * @var string[]|null
     */
    protected ?array $requiredPrimaryTypeNames = [];

    protected ?string $defaultPrimaryTypeName;
    protected bool $allowsSameNameSiblings;

    /**
     * Treat more information in addition to ItemDefinition::fromArray().
     *
     * See class documentation for the fields supported in the array.
     *
     * @param array{"declaringNodeType": string, "name": string, "isAutoCreated": boolean, "isMandatory": boolean, "isProtected": boolean, "onParentVersion": int, "allowsSameNameSiblings": boolean, "defaultPrimaryTypeName": string, "requiredPrimaryTypeNames"?: string[]} $data
     */
    protected function fromArray(array $data): void
    {
        parent::fromArray($data);
        $this->allowsSameNameSiblings = $data['allowsSameNameSiblings'];
        $this->defaultPrimaryTypeName = $data['defaultPrimaryTypeName'] ?? null;
        $this->requiredPrimaryTypeNames = (array_key_exists('requiredPrimaryTypeNames', $data) && count($data['requiredPrimaryTypeNames']))
                ? $data['requiredPrimaryTypeNames'] : [self::DEFAULT_PRIMARY_NODE];
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getRequiredPrimaryTypes(): ?array
    {
        // TODO if this is not attached to a live NodeType, return null
        if (empty($this->requiredPrimaryTypes)) {
            foreach ($this->requiredPrimaryTypeNames as $primaryTypeName) {
                $this->requiredPrimaryTypes[] = $this->nodeTypeManager->getNodeType($primaryTypeName);
            }
        }

        return $this->requiredPrimaryTypes;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getRequiredPrimaryTypeNames(): ?array
    {
        return $this->requiredPrimaryTypeNames;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDefaultPrimaryType()
    {
        if (null === $this->defaultPrimaryTypeName) {
            return null;
        }

        return $this->nodeTypeManager->getNodeType($this->defaultPrimaryTypeName);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDefaultPrimaryTypeName(): ?string
    {
        return $this->defaultPrimaryTypeName;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function allowsSameNameSiblings(): bool
    {
        return $this->allowsSameNameSiblings;
    }
}
