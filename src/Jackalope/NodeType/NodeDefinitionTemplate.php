<?php
namespace Jackalope\NodeType;

// inherit all doc
/**
 * @api
 */
class NodeDefinitionTemplate extends NodeDefinition implements \PHPCR\NodeType\NodeDefinitionTemplateInterface
{
    /**
     * Create a new node definition template instance.
     *
     * @param object $factory an object factory implementing "get" as
     *      described in \Jackalope\Factory
     * @param NodeTypeManager $nodeTypeManager
     */
    public function __construct($factory, NodeTypeManager $nodeTypeManager)
    {
        $this->factory = $factory;
        $this->nodeTypeManager = $nodeTypeManager;

        // initialize empty values
        $this->name = null;
        $this->isAutoCreated = false;
        $this->isMandatory = false;
        $this->onParentVersion = \PHPCR\Version\OnParentVersionAction::COPY;
        $this->isProtected = false;
        $this->requiredPrimaryTypeNames = null;
        $this->defaultPrimaryTypeName = null;
        $this->allowsSameNameSiblings = false;
    }

    // inherit all doc
    /**
     * @api
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    // inherit all doc
    /**
     * @api
     */
    public function setAutoCreated($autoCreated)
    {
        $this->isAutoCreated = $autoCreated;
    }

    // inherit all doc
    /**
     * @api
     */
    public function setMandatory($mandatory)
    {
        $this->isMandatory = $mandatory;
    }

    // inherit all doc
    /**
     * @api
     */
    public function setOnParentVersion($opv)
    {
        $this->onParentVersion = $opv;
    }

    // inherit all doc
    /**
     * @api
     */
    public function setProtected($protectedStatus)
    {
        $this->isProtected = $protectedStatus;
    }

    // inherit all doc
    /**
     * @api
     */
    public function setRequiredPrimaryTypeNames(array $requiredPrimaryTypeNames)
    {
        $this->requiredPrimaryTypeNames = $requiredPrimaryTypeNames;
    }

    // inherit all doc
    /**
     * @api
     */
    public function setDefaultPrimaryTypeName($defaultPrimaryTypeName)
    {
        $this->defaultPrimaryTypeName = $defaultPrimaryTypeName;
    }

    // inherit all doc
    /**
     * @api
     */
    public function setSameNameSiblings($allowSameNameSiblings)
    {
        $this->allowsSameNameSiblings = $allowSameNameSiblings;
    }

}

