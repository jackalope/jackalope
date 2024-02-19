<?php

namespace Jackalope\NodeType;

use Jackalope\FactoryInterface;
use PHPCR\NodeType\NodeTypeDefinitionInterface;
use PHPCR\Util\ValueConverter;

/**
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
class NodeTypeDefinition implements NodeTypeDefinitionInterface
{
    private const NAME_NT_BASE = 'nt:base';

    protected FactoryInterface $factory;

    protected NodeTypeManager $nodeTypeManager;

    protected ValueConverter $valueConverter;

    /**
     * The name of this node type definition.
     */
    protected ?string $name = null;

    protected bool $isAbstract = false;

    /**
     * Whether this is a mixin node type (otherwise it's a primary node type).
     */
    protected bool $isMixin = false;

    protected bool $isQueryable = true;

    protected bool $hasOrderableChildNodes = false;

    /**
     * Name of the primary item of this node type.
     */
    protected ?string $primaryItemName = null;

    protected ?array $declaredSuperTypeNames = null;

    protected \ArrayObject $declaredPropertyDefinitions;

    protected \ArrayObject $declaredNodeDefinitions;

    /**
     * Create a new node type definition.
     *
     * Optionally initializes the data from XML, an array or another
     * NodeTypeDefinition.
     *
     * @param FactoryInterface $factory the object factory
     * @param \DOMElement|NodeTypeDefinitionInterface|null
     *      $nodetype Either by XML or by NodeTypeDefinition or null for an
     *      empty definition
     *
     * @throws \InvalidArgumentException If it is not possible to read data
     *                                   from $nodetype
     */
    public function __construct(FactoryInterface $factory, NodeTypeManager $nodeTypeManager, $nodetype = null)
    {
        $this->factory = $factory;
        $this->valueConverter = $this->factory->get(ValueConverter::class);
        $this->nodeTypeManager = $nodeTypeManager;

        if ($nodetype instanceof \DOMElement) {
            $this->fromXml($nodetype);
        } elseif (is_array($nodetype)) {
            $this->fromArray($nodetype);
        } elseif ($nodetype instanceof NodeTypeDefinitionInterface) {
            $this->fromNodeTypeDefinition($nodetype); // copy constructor
        } elseif (!is_null($nodetype)) {
            throw new \InvalidArgumentException('Implementation Error -- unknown nodetype class: '.get_class($nodetype));
        }
    }

    /**
     * Read the node type definition from another NodeTypeDefinition.
     *
     * @param NodeTypeDefinitionInterface $ntd The node type
     *                                         definition to copy information from
     */
    protected function fromNodeTypeDefinition(NodeTypeDefinitionInterface $ntd): void
    {
        $this->name = $ntd->getName();
        $this->isAbstract = $ntd->isAbstract();
        $this->isMixin = $ntd->isMixin();
        $this->isQueryable = $ntd->isQueryable();
        $this->hasOrderableChildNodes = $ntd->hasOrderableChildNodes();
        $this->primaryItemName = $ntd->getPrimaryItemName();
        $this->declaredSuperTypeNames = $ntd->getDeclaredSupertypeNames();
        $this->declaredPropertyDefinitions = new \ArrayObject($ntd->getDeclaredPropertyDefinitions() ?: []);
        $this->declaredNodeDefinitions = new \ArrayObject($ntd->getDeclaredChildNodeDefinitions() ?: []);
    }

    /**
     * Reads the node type definition from an array.
     *
     * @param array{"name": string, "isAbstract": bool, "isMixin": bool, "isQueryAble": bool, "hasOrderableChildNodex": bool, "primaryItemName": string, "declaredSuperTypeNames"?: string[], "declaredPropertyDefinitions": mixed[], "declaredNodeDefinitions": mixed[]} $data
     */
    protected function fromArray(array $data): void
    {
        $this->name = $data['name'];
        $this->isAbstract = $data['isAbstract'];
        $this->isMixin = $data['isMixin'];
        $this->isQueryable = $data['isQueryable'];
        $this->hasOrderableChildNodes = $data['hasOrderableChildNodes'];
        $this->primaryItemName = $data['primaryItemName'] ?: null;
        $this->declaredSuperTypeNames = (isset($data['declaredSuperTypeNames']) && count($data['declaredSuperTypeNames'])) ? $data['declaredSuperTypeNames'] : [];
        $this->declaredPropertyDefinitions = new \ArrayObject();

        foreach ($data['declaredPropertyDefinitions'] as $propertyDef) {
            $this->declaredPropertyDefinitions[] = $this->factory->get(
                PropertyDefinition::class,
                [$propertyDef, $this->nodeTypeManager]
            );
        }
        $this->declaredNodeDefinitions = new \ArrayObject();
        foreach ($data['declaredNodeDefinitions'] as $nodeDef) {
            $this->declaredNodeDefinitions[] = $this->factory->get(
                NodeDefinition::class,
                [$nodeDef, $this->nodeTypeManager]
            );
        }
    }

    /**
     * Reads the node type definition from an xml element.
     *
     * @param \DOMElement $node The dom element to read information from
     */
    protected function fromXml(\DOMElement $node): void
    {
        $nodeTypeXmlConverter = new NodeTypeXmlConverter($this->factory);
        $this->fromArray($nodeTypeXmlConverter->getNodeTypeDefinitionFromXml($node));
    }

    /**
     * @api
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @api
     */
    public function getDeclaredSupertypeNames(): array
    {
        if (null === $this->declaredSuperTypeNames) {
            return [self::NAME_NT_BASE];
        }

        return $this->declaredSuperTypeNames;
    }

    /**
     * @api
     */
    public function isAbstract(): bool
    {
        return $this->isAbstract;
    }

    /**
     * @api
     */
    public function isMixin(): bool
    {
        return $this->isMixin;
    }

    /**
     * @api
     */
    public function hasOrderableChildNodes(): bool
    {
        return $this->hasOrderableChildNodes;
    }

    /**
     * @api
     */
    public function isQueryable(): bool
    {
        return $this->isQueryable;
    }

    /**
     * @api
     */
    public function getPrimaryItemName(): ?string
    {
        return $this->primaryItemName;
    }

    /**
     * @api
     */
    public function getDeclaredPropertyDefinitions(): ?array
    {
        return isset($this->declaredPropertyDefinitions) ? $this->declaredPropertyDefinitions->getArrayCopy() : null;
    }

    /**
     * @api
     */
    public function getDeclaredChildNodeDefinitions(): ?array
    {
        return isset($this->declaredNodeDefinitions) ? $this->declaredNodeDefinitions->getArrayCopy() : null;
    }
}
