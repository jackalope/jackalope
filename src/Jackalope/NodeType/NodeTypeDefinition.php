<?php

namespace Jackalope\NodeType;

use DOMElement;
use ArrayObject;
use InvalidArgumentException;
use PHPCR\NodeType\NodeTypeDefinitionInterface;
use Jackalope\FactoryInterface;
use PHPCR\Util\ValueConverter;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
class NodeTypeDefinition implements NodeTypeDefinitionInterface
{
    const NAME_NT_BASE = 'nt:base';

    /**
     * The factory to instantiate objects
     * @var FactoryInterface
     */
    protected $factory;

    /**
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @var ValueConverter
     */
    protected $valueConverter;

    /**
     * The name of this node type definition.
     * @var string
     */
    protected $name = null;

    /**
     * @var boolean
     */
    protected $isAbstract = false;

    /**
     * Whether this is a mixin node type (otherwise it's a primary node type).
     * @var boolean
     */
    protected $isMixin = false;

    /**
     * @var boolean
     */
    protected $isQueryable = true;

    /**
     * @var boolean
     */
    protected $hasOrderableChildNodes = false;

    /**
     * Name of the primary item of this node type.
     * @var string
     */
    protected $primaryItemName = null;

    /**
     * @var array
     */
    protected $declaredSuperTypeNames = null;

    /**
     * @var ArrayObject
     */
    protected $declaredPropertyDefinitions = null;

    /**
     * @var ArrayObject
     */
    protected $declaredNodeDefinitions = null;

    /**
     * Create a new node type definition.
     *
     * Optionally initializes the data from XML, an array or another
     * NodeTypeDefinition.
     *
     * @param FactoryInterface $factory         the object factory
     * @param NodeTypeManager  $nodeTypeManager
     * @param DOMElement|NodeTypeDefinitionInterface|null
     *      $nodetype Either by XML or by NodeTypeDefinition or null for an
     *      empty definition
     *
     * @throws InvalidArgumentException If it is not possible to read data
     *      from $nodetype
     */
    public function __construct(FactoryInterface $factory, NodeTypeManager $nodeTypeManager, $nodetype = null)
    {
        $this->factory = $factory;
        $this->valueConverter = $this->factory->get(ValueConverter::class);
        $this->nodeTypeManager = $nodeTypeManager;

        if ($nodetype instanceof DOMElement) {
            $this->fromXml($nodetype);
        } elseif (is_array($nodetype)) {
            $this->fromArray($nodetype);
        } elseif ($nodetype instanceof NodeTypeDefinitionInterface) {
            $this->fromNodeTypeDefinition($nodetype); // copy constructor
        } elseif (!is_null($nodetype)) {
            throw new InvalidArgumentException('Implementation Error -- unknown nodetype class: '.get_class($nodetype));
        }
    }

    /**
     * Read the node type definition from another NodeTypeDefinition
     *
     * @param NodeTypeDefinitionInterface $ntd The node type
     *      definition to copy information from
     */
    protected function fromNodeTypeDefinition(NodeTypeDefinitionInterface $ntd)
    {
        $this->name = $ntd->getName();
        $this->isAbstract = $ntd->isAbstract();
        $this->isMixin = $ntd->isMixin();
        $this->isQueryable = $ntd->isQueryable();
        $this->hasOrderableChildNodes = $ntd->hasOrderableChildNodes();
        $this->primaryItemName = $ntd->getPrimaryItemName();
        $this->declaredSuperTypeNames = $ntd->getDeclaredSupertypeNames();
        $this->declaredPropertyDefinitions = new ArrayObject($ntd->getDeclaredPropertyDefinitions() ?: []);
        $this->declaredNodeDefinitions = new ArrayObject($ntd->getDeclaredChildNodeDefinitions() ?: []);
    }

    /**
     * Reads the node type definition from an array
     *
     * @param array $data an array with key-value information
     */
    protected function fromArray(array $data)
    {
        $this->name = $data['name'];
        $this->isAbstract = $data['isAbstract'];
        $this->isMixin = $data['isMixin'];
        $this->isQueryable = $data['isQueryable'];
        $this->hasOrderableChildNodes = $data['hasOrderableChildNodes'];
        $this->primaryItemName = $data['primaryItemName'] ?: null;
        $this->declaredSuperTypeNames = (isset($data['declaredSuperTypeNames']) && count($data['declaredSuperTypeNames'])) ? $data['declaredSuperTypeNames'] : [];
        $this->declaredPropertyDefinitions = new ArrayObject();

        foreach ($data['declaredPropertyDefinitions'] as $propertyDef) {
            $this->declaredPropertyDefinitions[] = $this->factory->get(
                PropertyDefinition::class,
                [$propertyDef, $this->nodeTypeManager]
            );
        }
        $this->declaredNodeDefinitions = new ArrayObject();
        foreach ($data['declaredNodeDefinitions'] as $nodeDef) {
            $this->declaredNodeDefinitions[] = $this->factory->get(
                NodeDefinition::class,
                [$nodeDef, $this->nodeTypeManager]
            );
        }
    }

    /**
     * Reads the node type definition from an xml element
     *
     * @param DOMElement $node The dom element to read information from
     */
    protected function fromXml(DOMElement $node)
    {
        $nodeTypeXmlConverter = new NodeTypeXmlConverter($this->factory);
        $this->fromArray($nodeTypeXmlConverter->getNodeTypeDefinitionFromXml($node));
    }

    /**
     * {@inheritDoc}
     *
     * @return string the name of the node type
     *
     * @api
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     *
     * @return string[]
     *
     * @api
     */
    public function getDeclaredSupertypeNames()
    {
        if (null === $this->declaredSuperTypeNames) {
            return [self::NAME_NT_BASE];
        }

        return $this->declaredSuperTypeNames;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool true, if the current type is abstract, else false
     *
     * @api
     */
    public function isAbstract()
    {
        return $this->isAbstract;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool True if this is a mixin type, else false;
     *
     * @api
     */
    public function isMixin()
    {
        return $this->isMixin;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool true, if nodes of this type must support orderable child
     *              nodes, else false
     *
     * @api
     */
    public function hasOrderableChildNodes()
    {
        return $this->hasOrderableChildNodes;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool true, if the node type is queryable, else false
     *
     * @api
     */
    public function isQueryable()
    {
        return $this->isQueryable;
    }

    /**
     * {@inheritDoc}
     *
     * @return string the name of the primary item
     *
     * @api
     */
    public function getPrimaryItemName()
    {
        return $this->primaryItemName;
    }

    /**
     * {@inheritDoc}
     *
     * @return \PHPCR\NodeType\PropertyDefinitionInterface[]
     *
     * @api
     */
    public function getDeclaredPropertyDefinitions()
    {
        return null === $this->declaredPropertyDefinitions
            ? null : $this->declaredPropertyDefinitions->getArrayCopy();
    }

    /**
     * {@inheritDoc}
     *
     * @return \PHPCR\NodeType\NodeDefinitionInterface[]
     *
     * @api
     */
    public function getDeclaredChildNodeDefinitions()
    {
        return null === $this->declaredNodeDefinitions
            ? null : $this->declaredNodeDefinitions->getArrayCopy();
    }
}
