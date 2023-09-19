<?php

namespace Jackalope\NodeType;

use Jackalope\FactoryInterface;
use PHPCR\NodeType\NodeDefinitionTemplateInterface;
use PHPCR\Version\OnParentVersionAction;

/**
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
class NodeDefinitionTemplate extends NodeDefinition implements NodeDefinitionTemplateInterface
{
    /**
     * Create a new node definition template instance.
     *
     * @param FactoryInterface $factory the object factory
     */
    public function __construct(FactoryInterface $factory, NodeTypeManager $nodeTypeManager)
    {
        $this->factory = $factory;
        $this->nodeTypeManager = $nodeTypeManager;

        // initialize empty values
        $this->name = null;
        $this->isAutoCreated = false;
        $this->isMandatory = false;
        $this->onParentVersion = OnParentVersionAction::COPY;
        $this->isProtected = false;
        $this->requiredPrimaryTypeNames = null;
        $this->defaultPrimaryTypeName = null;
        $this->allowsSameNameSiblings = false;
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
    public function setRequiredPrimaryTypeNames(array $requiredPrimaryTypeNames): void
    {
        $this->requiredPrimaryTypeNames = $requiredPrimaryTypeNames;
    }

    /**
     * @api
     */
    public function setDefaultPrimaryTypeName($defaultPrimaryTypeName): void
    {
        $this->defaultPrimaryTypeName = $defaultPrimaryTypeName;
    }

    /**
     * @api
     */
    public function setSameNameSiblings($allowSameNameSiblings): void
    {
        $this->allowsSameNameSiblings = $allowSameNameSiblings;
    }
}
