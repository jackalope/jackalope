<?php

namespace Jackalope\NodeType;

use PHPCR\NodeType\NodeTypeTemplateInterface;

/**
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
class NodeTypeTemplate extends NodeTypeDefinition implements NodeTypeTemplateInterface
{
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
    public function setDeclaredSuperTypeNames(array $names): void
    {
        $this->declaredSuperTypeNames = $names;
    }

    /**
     * @api
     */
    public function setAbstract($abstractStatus): void
    {
        $this->isAbstract = $abstractStatus;
    }

    /**
     * @api
     */
    public function setMixin($mixin): void
    {
        $this->isMixin = $mixin;
    }

    /**
     * @api
     */
    public function setOrderableChildNodes($orderable): void
    {
        $this->hasOrderableChildNodes = $orderable;
    }

    /**
     * @api
     */
    public function setPrimaryItemName($name): void
    {
        $this->primaryItemName = $name;
    }

    /**
     * @api
     */
    public function setQueryable($queryable): void
    {
        $this->isQueryable = $queryable;
    }

    /**
     * @api
     */
    public function getPropertyDefinitionTemplates(): \ArrayObject
    {
        if (!isset($this->declaredPropertyDefinitions)) {
            $this->declaredPropertyDefinitions = new \ArrayObject();
        }

        return $this->declaredPropertyDefinitions;
    }

    /**
     * @api
     */
    public function getNodeDefinitionTemplates(): \ArrayObject
    {
        if (!isset($this->declaredNodeDefinitions)) {
            $this->declaredNodeDefinitions = new \ArrayObject();
        }

        return $this->declaredNodeDefinitions;
    }
}
