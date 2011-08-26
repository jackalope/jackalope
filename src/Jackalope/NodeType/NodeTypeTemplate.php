<?php
namespace Jackalope\NodeType;
use \ArrayObject;

// inherit all doc
/**
 * @api
 */
class NodeTypeTemplate extends NodeTypeDefinition implements \PHPCR\NodeType\NodeTypeTemplateInterface
{
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
    public function setDeclaredSuperTypeNames(array $names)
    {
        $this->declaredSuperTypeNames = $names;
    }

    // inherit all doc
    /**
     * @api
     */
    public function setAbstract($abstractStatus)
    {
        $this->isAbstract = $abstractStatus;
    }

    // inherit all doc
    /**
     * @api
     */
    public function setMixin($mixin)
    {
        $this->isMixin = $mixin;
    }

    // inherit all doc
    /**
     * @api
     */
    public function setOrderableChildNodes($orderable)
    {
        $this->hasOrderableChildNodes = $orderable;
    }

    // inherit all doc
    /**
     * @api
     */
    public function setPrimaryItemName($name)
    {
        $this->primaryItemName = $name;
    }

    // inherit all doc
    /**
     * @api
     */
    public function setQueryable($queryable)
    {
        $this->isQueryable = $queryable;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getPropertyDefinitionTemplates()
    {
        if (is_null($this->declaredPropertyDefinitions)) {
            $this->declaredPropertyDefinitions = new ArrayObject();
        }
        return $this->declaredPropertyDefinitions;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getNodeDefinitionTemplates()
    {
        if (is_null($this->declaredNodeDefinitions)) {
            $this->declaredNodeDefinitions = new ArrayObject();
        }
        return $this->declaredNodeDefinitions;
    }

}
