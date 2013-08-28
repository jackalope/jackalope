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
    protected $requiredPrimaryTypes = array();
    /**
     * List of required primary type names as string.
     * @var array
     */
    protected $requiredPrimaryTypeNames = array();
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
                ? $data['requiredPrimaryTypeNames'] : array(self::DEFAULT_PRIMARY_NODE);
    }

    /**
     * {@inheritDoc}
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
     * @api
     */
    public function getRequiredPrimaryTypeNames()
    {
        return $this->requiredPrimaryTypeNames;
    }

    /**
     * {@inheritDoc}
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
     * @api
     */
    public function getDefaultPrimaryTypeName()
    {
        return $this->defaultPrimaryTypeName;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function allowsSameNameSiblings()
    {
        return $this->allowsSameNameSiblings;
    }
}
