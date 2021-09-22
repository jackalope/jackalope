<?php

namespace Jackalope\NodeType;

use Jackalope\FactoryInterface;
use PHPCR\NodeType\ItemDefinitionInterface;
use PHPCR\NodeType\NoSuchNodeTypeException;
use PHPCR\RepositoryException;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
class ItemDefinition implements ItemDefinitionInterface
{
    protected FactoryInterface $factory;
    protected NodeTypeManager $nodeTypeManager;

    /**
     * Name of the declaring node type.
     */
    protected string $declaringNodeType;

    /**
     * Name of this node type.
     */
    protected ?string $name;

    protected bool $isAutoCreated;
    protected bool $isMandatory;
    protected bool $isProtected;
    protected int $onParentVersion;

    /**
     * Create a new item definition.
     *
     * TODO: document this format. Property and Node add more to this.
     *
     * @param FactoryInterface $factory    the object factory
     * @param array            $definition The property definition data as array
     */
    public function __construct(FactoryInterface $factory, array $definition, NodeTypeManager $nodeTypeManager)
    {
        $this->factory = $factory;
        $this->fromArray($definition);
        $this->nodeTypeManager = $nodeTypeManager;
    }

    /**
     * Load item definition from an array.
     *
     * Overwritten for property and node to add more information, with a call
     * to this parent method for the common things.
     *
     * @param array $data An array with the fields required by ItemDefinition
     */
    protected function fromArray(array $data): void
    {
        $this->declaringNodeType = $data['declaringNodeType'];
        $this->name = $data['name'];
        $this->isAutoCreated = $data['isAutoCreated'];
        $this->isMandatory = $data['isMandatory'];
        $this->isProtected = $data['isProtected'];
        $this->onParentVersion = $data['onParentVersion'];
    }

    /**
     * {@inheritDoc}
     *
     * @throws NoSuchNodeTypeException
     * @throws RepositoryException
     *
     * @api
     */
    public function getDeclaringNodeType()
    {
        return $this->nodeTypeManager->getNodeType($this->declaringNodeType);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isAutoCreated(): bool
    {
        return $this->isAutoCreated;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isMandatory(): bool
    {
        return $this->isMandatory;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getOnParentVersion(): int
    {
        return $this->onParentVersion;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isProtected(): bool
    {
        return $this->isProtected;
    }
}
