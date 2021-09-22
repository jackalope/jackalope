<?php

namespace Jackalope\NodeType;

use PHPCR\NodeType\NodeTypeTemplateInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
class NodeTypeTemplate extends NodeTypeDefinition implements NodeTypeTemplateInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setDeclaredSuperTypeNames(array $names): void
    {
        $this->declaredSuperTypeNames = $names;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setAbstract($abstractStatus): void
    {
        $this->isAbstract = $abstractStatus;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setMixin($mixin): void
    {
        $this->isMixin = $mixin;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setOrderableChildNodes($orderable): void
    {
        $this->hasOrderableChildNodes = $orderable;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setPrimaryItemName($name): void
    {
        $this->primaryItemName = $name;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setQueryable($queryable): void
    {
        $this->isQueryable = $queryable;
    }

    /**
     * {@inheritDoc}
     *
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
     * {@inheritDoc}
     *
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
