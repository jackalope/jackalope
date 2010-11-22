<?php
namespace Jackalope\NodeType;

/**
 * The NodeDefinitionTemplate interface extends NodeDefinition with the addition
 * of write methods, enabling the characteristics of a child node definition to
 * be set, after which the NodeDefinitionTemplate is added to a NodeTypeTemplate.
 *
 * See the corresponding get methods for each attribute in NodeDefinition for the
 * default values assumed when a new empty NodeDefinitionTemplate is created (as
 * opposed to one extracted from an existing NodeType).
 */
class NodeDefinitionTemplate extends NodeDefinition implements \PHPCR\NodeType\NodeDefinitionTemplateInterface
{
    public function __construct(NodeTypeManager $nodeTypeManager)
    {
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

    /**
     * Sets the name of the node.
     *
     * @param string $name a String.
     * @return void
     * @api
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Sets the auto-create status of the node.
     *
     * @param boolean $autoCreated a boolean.
     * @return void
     * @api
     */
    public function setAutoCreated($autoCreated)
    {
        $this->isAutoCreated = $autoCreated;
    }

    /**
     * Sets the mandatory status of the node.
     *
     * @param boolean $mandatory a boolean.
     * @return void
     * @api
     */
    public function setMandatory($mandatory)
    {
        $this->isMandatory = $mandatory;
    }

    /**
     * Sets the on-parent-version status of the node.
     *
     * @param integer $opv an int constant member of OnParentVersionAction.
     * @return void
     * @api
     */
    public function setOnParentVersion($opv)
    {
        $this->onParentVersion = $opv;
    }

    /**
     * Sets the protected status of the node.
     *
     * @param boolean $protectedStatus a boolean.
     * @return void
     * @api
     */
    public function setProtected($protectedStatus)
    {
        $this->isProtected = $protectedStatus;
    }

    /**
     * Sets the names of the required primary types of this node.
     *
     * @param array $requiredPrimaryTypeNames a String array.
     * @return void
     * @api
     */
    public function setRequiredPrimaryTypeNames(array $requiredPrimaryTypeNames)
    {
        $this->requiredPrimaryTypeNames = $requiredPrimaryTypeNames;
    }

    /**
     * Sets the name of the default primary type of this node.
     *
     * @param string $defaultPrimaryTypeName a String.
     * @return void
     * @api
     */
    public function setDefaultPrimaryTypeName($defaultPrimaryTypeName)
    {
        $this->defaultPrimaryTypeName = $defaultPrimaryTypeName;
    }

    /**
     * Sets the same-name sibling status of this node.
     *
     * @param boolean $allowSameNameSiblings a boolean.
     * @return void
     * @api
     */
    public function setSameNameSiblings($allowSameNameSiblings)
    {
        $this->allowsSameNameSiblings = $allowSameNameSiblings;
    }

}

