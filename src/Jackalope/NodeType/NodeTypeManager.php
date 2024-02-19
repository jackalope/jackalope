<?php

namespace Jackalope\NodeType;

use Jackalope\FactoryInterface;
use Jackalope\NotImplementedException;
use Jackalope\ObjectManager;
use PHPCR\AccessDeniedException;
use PHPCR\NamespaceException;
use PHPCR\NamespaceRegistryInterface;
use PHPCR\NodeType\NodeTypeDefinitionInterface;
use PHPCR\NodeType\NodeTypeExistsException;
use PHPCR\NodeType\NodeTypeInterface;
use PHPCR\NodeType\NodeTypeManagerInterface;
use PHPCR\NodeType\NoSuchNodeTypeException;
use PHPCR\RepositoryException;

/**
 * {@inheritDoc}
 *
 * In Jackalope, we try to do lazy fetching of node types to reduce overhead.
 * Jackalope supports registering node types, and when using the jackrabbit for
 * transport, there is an additional method registerNodeTypesCnd for the
 * jackrabbit specific textual node type specification.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
class NodeTypeManager implements \IteratorAggregate, NodeTypeManagerInterface
{
    private FactoryInterface $factory;

    private ObjectManager $objectManager;

    private NamespaceRegistryInterface $namespaceRegistry;

    /**
     * Cache of already fetched primary node type instances.
     */
    private array $primaryTypes;

    /**
     * Cache of already fetched mixin node type instances.
     */
    private array $mixinTypes;

    /**
     * Array of arrays with the super type as key and its sub types as values.
     */
    private array $nodeTree = [];

    /**
     * Flag to only load all node types from the backend once.
     *
     * Methods like hasNodeType() need to fetch all node types. Others like
     * getNodeType() do not need all, but just the requested one. Unless we
     * need all, we only load specific ones and cache them.
     */
    private bool $fetchedAllFromBackend = false;

    /**
     * Create the node type manager for a session.
     *
     * There may be only one instance per session
     */
    public function __construct(
        FactoryInterface $factory,
        ObjectManager $objectManager,
        NamespaceRegistryInterface $namespaceRegistry
    ) {
        $this->factory = $factory;
        $this->objectManager = $objectManager;
        $this->namespaceRegistry = $namespaceRegistry;
    }

    /**
     * Fetch a node type from backend.
     *
     * Without a filter parameter, this will fetch all node types from the backend.
     *
     * It is no problem to call this method with null as name, it will remember
     * once it fetched all node types and do nothing after that.
     *
     * On fetch all, already cached node types are kept.
     *
     * @param string|null $name type name to fetch. defaults to null which will
     *                          fetch all nodes.
     */
    private function fetchNodeTypes(?string $name = null): void
    {
        if ($this->fetchedAllFromBackend) {
            return;
        }

        if (null !== $name) {
            if (!empty($this->primaryTypes[$name]) || !empty($this->mixinTypes[$name])) {
                return; // we already know this node
            }

            // OPTIMIZE: also avoid trying to fetch nonexisting definitions we already tried to get
            $nodeTypes = $this->objectManager->getNodeType($name);
        } else {
            $nodeTypes = $this->objectManager->getNodeTypes();
            $this->fetchedAllFromBackend = true;
        }

        foreach ($nodeTypes as $nodeType) {
            /** @var NodeType $nodeType */
            $nodeType = $this->factory->get(NodeType::class, [$this, $nodeType]);
            $name = $nodeType->getName();
            // do not overwrite existing types. maybe they where changed locally
            if (empty($this->primaryTypes[$name]) && empty($this->mixinTypes[$name])) {
                $this->addNodeType($nodeType);
            }
        }
    }

    /**
     * Stores the node type in our internal structures (flat && tree).
     *
     * @param NodeTypeInterface $nodeType The nodetype to add
     */
    private function addNodeType(NodeTypeInterface $nodeType): void
    {
        if ($nodeType->isMixin()) {
            $this->mixinTypes[$nodeType->getName()] = $nodeType;
        } else {
            $this->primaryTypes[$nodeType->getName()] = $nodeType;
        }
        $this->addToNodeTree($nodeType);
    }

    /**
     * Helper method for node types: Returns the declared subtypes of a given
     * nodename.
     *
     * @return array<string, NodeTypeInterface> Names of the subnode type pointing to node type instances
     *
     * @see NodeType::getDeclaredSubtypes
     *
     * @private
     */
    public function getDeclaredSubtypes(string $nodeTypeName): array
    {
        // OPTIMIZE: any way to avoid loading all nodes at this point?
        $this->fetchNodeTypes();

        if (empty($this->nodeTree[$nodeTypeName])) {
            return [];
        }

        return $this->nodeTree[$nodeTypeName];
    }

    /**
     * Helper method for NodeType: Returns all sub types of a node and their
     * sub types.
     *
     * @return array<string, NodeTypeInterface> with the names of the subnode types pointing to the node type instances
     *
     * @see NodeType::getSubtypes
     *
     * @private
     */
    public function getSubtypes(string $nodeTypeName): array
    {
        // OPTIMIZE: any way to avoid loading all nodes at this point?
        $this->fetchNodeTypes();
        $ret = [];
        if (isset($this->nodeTree[$nodeTypeName])) {
            foreach ($this->nodeTree[$nodeTypeName] as $name => $subnode) {
                $ret = array_merge($ret, [$name => $subnode], $this->getSubtypes($name));
            }
        }

        return $ret;
    }

    /**
     * Adds the declared super types of a node type to the tree to be able to
     * fetch the sub types of those super types later on.
     *
     * Part of addNodeType.
     */
    private function addToNodeTree(NodeTypeInterface $nodetype): void
    {
        foreach ($nodetype->getDeclaredSupertypeNames() as $declaredSupertypeName) {
            if (isset($this->nodeTree[$declaredSupertypeName])) {
                $this->nodeTree[$declaredSupertypeName] =
                    array_merge(
                        $this->nodeTree[$declaredSupertypeName],
                        [$nodetype->getName() => $nodetype]
                    );
            } else {
                $this->nodeTree[$declaredSupertypeName] = [$nodetype->getName() => $nodetype];
            }
        }
    }

    /**
     * @api
     */
    public function getNodeType($nodeTypeName): NodeTypeInterface
    {
        if (null === $nodeTypeName) {
            throw new NoSuchNodeTypeException('nodeTypeName is <null>');
        }

        if ('' === $nodeTypeName) {
            throw new NoSuchNodeTypeException('nodeTypeName is empty string');
        }

        if ('{' === $nodeTypeName[0]) {
            list($uri, $name) = explode('}', substr($nodeTypeName, 1));
            $prefix = $this->namespaceRegistry->getPrefix($uri);
            $nodeTypeName = "$prefix:$name";
        }

        $this->fetchNodeTypes($nodeTypeName);

        if (isset($this->primaryTypes[$nodeTypeName])) {
            return $this->primaryTypes[$nodeTypeName];
        }

        if (isset($this->mixinTypes[$nodeTypeName])) {
            return $this->mixinTypes[$nodeTypeName];
        }

        throw new NoSuchNodeTypeException($nodeTypeName);
    }

    /**
     * @api
     */
    public function hasNodeType($name): bool
    {
        try {
            $this->fetchNodeTypes($name);
        } catch (NoSuchNodeTypeException $e) {
            // if we have not yet fetched all types and this type is not existing
            // we get an exception. just ignore the exception, we don't have the type.
            return false;
        }

        return isset($this->primaryTypes[$name]) || isset($this->mixinTypes[$name]);
    }

    /**
     * @api
     */
    public function getAllNodeTypes(): \Iterator
    {
        $this->fetchNodeTypes();

        return new \ArrayIterator(array_merge($this->primaryTypes, $this->mixinTypes));
    }

    /**
     * @api
     */
    public function getPrimaryNodeTypes(): \Iterator
    {
        $this->fetchNodeTypes();

        return new \ArrayIterator($this->primaryTypes);
    }

    /**
     * @api
     */
    public function getMixinNodeTypes(): \Iterator
    {
        $this->fetchNodeTypes();

        return new \ArrayIterator($this->mixinTypes);
    }

    /**
     * @api
     */
    public function createNodeTypeTemplate($ntd = null): NodeTypeTemplate
    {
        return $this->factory->get(NodeTypeTemplate::class, [$this, $ntd]);
    }

    /**
     * @api
     */
    public function createNodeDefinitionTemplate(): NodeDefinitionTemplate
    {
        return $this->factory->get(NodeDefinitionTemplate::class, [$this]);
    }

    /**
     * @api
     */
    public function createPropertyDefinitionTemplate(): PropertyDefinitionTemplate
    {
        return $this->factory->get(PropertyDefinitionTemplate::class, [$this]);
    }

    /**
     * @api
     */
    public function registerNodeType(NodeTypeDefinitionInterface $ntd, $allowUpdate): NodeTypeDefinitionInterface
    {
        $this->registerNodeTypes([$ntd], $allowUpdate);

        return $ntd;
    }

    /**
     * Internally create a node type object.
     *
     * @param bool $allowUpdate whether updating the definition is to be allowed or not
     *
     * @return NodeType the new node type
     *
     * @throws RepositoryException
     * @throws NodeTypeExistsException
     */
    private function createNodeType(NodeTypeDefinitionInterface $ntd, bool $allowUpdate): NodeType
    {
        if (!$allowUpdate && $this->hasNodeType($ntd->getName())) {
            throw new NodeTypeExistsException('NodeType already existing: '.$ntd->getName());
        }

        return $this->factory->get(NodeType::class, [$this, $ntd]);
    }

    /**
     * @api
     */
    public function registerNodeTypes(array $definitions, $allowUpdate): \Iterator
    {
        $nts = [];

        // prepare them first (all or nothing)
        foreach ($definitions as $definition) {
            $nts[$definition->getName()] = $this->createNodeType($definition, $allowUpdate);
        }

        $this->objectManager->registerNodeTypes($definitions, $allowUpdate);

        // no need to fetch the node types as with cnd, we already have the def and can
        // now register them ourselves
        foreach ($nts as $nt) {
            $this->addNodeType($nt);
        }

        return new \ArrayIterator($nts);
    }

    /**
     * @throws AccessDeniedException
     * @throws NamespaceException
     *
     * @api
     */
    public function registerNodeTypesCnd($cnd, $allowUpdate): array
    {
        // set fetched from backend to false to allow to load the new types from backend
        $fetched = $this->fetchedAllFromBackend;
        $this->fetchedAllFromBackend = false;
        $this->objectManager->registerNodeTypesCnd($cnd, $allowUpdate);

        // parse out type names and fetch types to return definitions of the new nodes
        preg_match_all('/\[([^\]]*)\]/', $cnd, $names);
        $types = [];
        foreach ($names[1] as $name) {
            $types[$name] = $this->getNodeType($name);
        }
        $this->fetchedAllFromBackend = $fetched;

        return $types;
    }

    /**
     * @api
     */
    public function unregisterNodeType($name): void
    {
        if (!empty($this->primaryTypes[$name])) {
            unset($this->primaryTypes[$name]);
        } elseif (!empty($this->mixinTypes[$name])) {
            unset($this->mixinTypes[$name]);
        } else {
            throw new NoSuchNodeTypeException('NodeType not found: '.$name);
        }

        throw new NotImplementedException('TODO: remove from nodeTree and register with server (jackrabbit has not implemented this yet)');
    }

    /**
     * @api
     */
    public function unregisterNodeTypes(array $names): void
    {
        foreach ($names as $name) {
            $this->unregisterNodeType($name);
        }
    }

    /**
     * Provide Traversable interface: redirect to getAllNodeTypes.
     *
     * @return \Iterator over all node types
     *
     * @throws RepositoryException
     */
    public function getIterator(): \Traversable
    {
        return $this->getAllNodeTypes();
    }
}
