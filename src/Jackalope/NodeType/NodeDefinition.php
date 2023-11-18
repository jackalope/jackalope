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
    const DEFAULT_PRIMARY_NODE = 'nt:base';

    /**
     * Cached list of NodeType instances populated in first call to getRequiredPrimaryTypes
     * @var array
     */
    protected $requiredPrimaryTypes = [];

    /**
     * List of required primary type names as string.
     * @var array
     */
    protected $requiredPrimaryTypeNames = [];

    /**
     * @var string
     */
    protected $defaultPrimaryTypeName;

    /**
     * @var boolean
     */
    protected $allowsSameNameSiblings;

    /**
     * Treat more information in addition to ItemDefinition::fromArray()
     *
     * See class documentation for the fields supported in the array.
     *
     * @param array $data The node definition in array form.
     */
    protected function fromArray(array $data)
    {
        parent::fromArray($data);
        $this->allowsSameNameSiblings = $data['allowsSameNameSiblings'];
        $this->defaultPrimaryTypeName = isset($data['defaultPrimaryTypeName']) ? $data['defaultPrimaryTypeName'] : null;
        $this->requiredPrimaryTypeNames = (isset($data['requiredPrimaryTypeNames']) && count($data['requiredPrimaryTypeNames']))
                ? $data['requiredPrimaryTypeNames'] : [self::DEFAULT_PRIMARY_NODE];
    }

    /**
     * {@inheritDoc}
     *
     * @return \PHPCR\NodeType\NodeTypeInterface[] an array of NodeType objects
     *
     * @api
     */
    public function getRequiredPrimaryTypes()
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
     * @return string[]
     *
     * @api
     */
    public function getRequiredPrimaryTypeNames()
    {
        return $this->requiredPrimaryTypeNames;
    }

    /**
     * {@inheritDoc}
     *
     * @return \PHPCR\NodeType\NodeTypeInterface a NodeType
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
     * @return string the name of the default primary type
     *
     * @api
     */
    public function getDefaultPrimaryTypeName()
    {
        return $this->defaultPrimaryTypeName;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool true, if the node my have a same-name sibling, else false
     *
     * @api
     */
    public function allowsSameNameSiblings()
    {
        return $this->allowsSameNameSiblings;
    }
}
