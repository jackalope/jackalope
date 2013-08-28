<?php

namespace Jackalope\NodeType;

use PHPCR\NodeType\NodeDefinitionTemplateInterface;
use PHPCR\Version\OnParentVersionAction;

use Jackalope\FactoryInterface;

/**
 * {@inheritDoc}
 *
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
     * @param FactoryInterface $factory         the object factory
     * @param NodeTypeManager  $nodeTypeManager
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
    public function setRequiredPrimaryTypeNames(array $requiredPrimaryTypeNames)
    {
        $this->requiredPrimaryTypeNames = $requiredPrimaryTypeNames;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setDefaultPrimaryTypeName($defaultPrimaryTypeName)
    {
        $this->defaultPrimaryTypeName = $defaultPrimaryTypeName;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setSameNameSiblings($allowSameNameSiblings)
    {
        $this->allowsSameNameSiblings = $allowSameNameSiblings;
    }
}
