<?php

namespace Jackalope\NodeType;

use ArrayObject;

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
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setDeclaredSuperTypeNames(array $names)
    {
        $this->declaredSuperTypeNames = $names;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setAbstract($abstractStatus)
    {
        $this->isAbstract = $abstractStatus;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setMixin($mixin)
    {
        $this->isMixin = $mixin;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setOrderableChildNodes($orderable)
    {
        $this->hasOrderableChildNodes = $orderable;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setPrimaryItemName($name)
    {
        $this->primaryItemName = $name;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setQueryable($queryable)
    {
        $this->isQueryable = $queryable;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getPropertyDefinitionTemplates()
    {
        if (is_null($this->declaredPropertyDefinitions)) {
            $this->declaredPropertyDefinitions = new ArrayObject();
        }

        return $this->declaredPropertyDefinitions;
    }

    /**
     * {@inheritDoc}
     *
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
