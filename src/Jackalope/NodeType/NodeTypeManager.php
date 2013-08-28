<?php

namespace Jackalope\NodeType;

use IteratorAggregate;
use ArrayIterator;

use Jackalope\NamespaceRegistry;
use PHPCR\NamespaceRegistryInterface;
use PHPCR\NodeType\NodeTypeInterface;
use PHPCR\NodeType\NodeTypeDefinitionInterface;
use PHPCR\NodeType\NodeTypeManagerInterface;
use PHPCR\NodeType\NoSuchNodeTypeException;
use PHPCR\NodeType\NodeTypeExistsException;

use Jackalope\ObjectManager;
use Jackalope\NotImplementedException;
use Jackalope\FactoryInterface;

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
class NodeTypeManager implements IteratorAggregate, NodeTypeManagerInterface
{
    /**
     * The factory to instantiate objects.
     * @var FactoryInterface
     */
    protected $factory;
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var NamespaceRegistryInterface
     */
    protected $namespaceRegistry;

    /**
     * Cache of already fetched primary node type instances.
     * @var array
     */
    protected $primaryTypes;
    /**
     * Cache of already fetched mixin node type instances.
     * @var array
     */
    protected $mixinTypes;
    /**
     * Array of arrays with the super type as key and its sub types as values.
     * @var array
     */
    protected $nodeTree = array();

    /**
     * Flag to only load all node types from the backend once.
     *
     * Methods like hasNodeType() need to fetch all node types. Others like
     * getNodeType() do not need all, but just the requested one. Unless we
     * need all, we only load specific ones and cache them.
     *
     * @var boolean
     */
    protected $fetchedAllFromBackend = false;

    /**
     * Create the node type manager for a session.
     *
     * There may be only one instance per session
     * @param FactoryInterface  $factory
     * @param ObjectManager     $objectManager
     * @param NamespaceRegistry $namespaceRegistry
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
     * @param string $name type name to fetch. defaults to null which will
     *      fetch all nodes.
     */
    protected function fetchNodeTypes($name = null)
    {
        if ($this->fetchedAllFromBackend) {
            return;
        }

        if (null !== $name) {
            if (!empty($this->primaryTypes[$name]) || !empty($this->mixinTypes[$name])) {
                return; //we already know this node
            }

            //OPTIMIZE: also avoid trying to fetch nonexisting definitions we already tried to get
            $nodetypes = $this->objectManager->getNodeType($name);
        } else {
            $nodetypes = $this->objectManager->getNodeTypes();
            $this->fetchedAllFromBackend = true;
        }

        foreach ($nodetypes as $nodetype) {
            $nodetype = $this->factory->get('NodeType\\NodeType', array($this, $nodetype));
            $name = $nodetype->getName();
            //do not overwrite existing types. maybe they where changed locally
            if (empty($this->primaryTypes[$name]) && empty($this->mixinTypes[$name])) {
                $this->addNodeType($nodetype);
            }
        }
    }

    /**
     * Stores the node type in our internal structures (flat && tree)
     *
     * @param NodeTypeInterface $nodetype The nodetype to add
     */
    protected function addNodeType(NodeTypeInterface $nodetype)
    {
        if ($nodetype->isMixin()) {
            $this->mixinTypes[$nodetype->getName()] = $nodetype;
        } else {
            $this->primaryTypes[$nodetype->getName()] = $nodetype;
        }
        $this->addToNodeTree($nodetype);
    }

    /**
     * Helper method for node types: Returns the declared subtypes of a given
     * nodename.
     *
     * @param string $nodeTypeName
     *
     * @return array with the names of the subnode types pointing to the node type instances
     *
     * @see NodeType::getDeclaredSubtypes
     *
     * @private
     */
    public function getDeclaredSubtypes($nodeTypeName)
    {
        // OPTIMIZE: any way to avoid loading all nodes at this point?
        $this->fetchNodeTypes();

        if (empty($this->nodeTree[$nodeTypeName])) {
            return array();
        }

        return $this->nodeTree[$nodeTypeName];
    }

    /**
     * Helper method for NodeType: Returns all sub types of a node and their
     * sub types.
     *
     * @param string $nodeTypeName
     *
     * @return array with the names of the subnode types pointing to the node type instances
     *
     * @see NodeType::getSubtypes
     *
     * @private
     */
    public function getSubtypes($nodeTypeName)
    {
        // OPTIMIZE: any way to avoid loading all nodes at this point?
        $this->fetchNodeTypes();
        $ret = array();
        if (isset($this->nodeTree[$nodeTypeName])) {
            foreach ($this->nodeTree[$nodeTypeName] as $name => $subnode) {
                $ret = array_merge($ret, array($name => $subnode), $this->getSubtypes($name));
            }
        }

        return $ret;
    }

    /**
     * Adds the declared super types of a node type to the tree to be able to
     * fetch the sub types of those super types later on.
     *
     * Part of addNodeType.
     *
     * @param NodeTypeInterface $nodetype the node type to add.
     */
    private function addToNodeTree($nodetype)
    {
        foreach ($nodetype->getDeclaredSupertypeNames() as $declaredSupertypeName) {
            if (isset($this->nodeTree[$declaredSupertypeName])) {
                $this->nodeTree[$declaredSupertypeName] =
                    array_merge($this->nodeTree[$declaredSupertypeName],
                                array($nodetype->getName() => $nodetype)
                               );
            } else {
                $this->nodeTree[$declaredSupertypeName] = array($nodetype->getName() => $nodetype);
            }
        }
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getNodeType($nodeTypeName)
    {
        if (null === $nodeTypeName) {
            throw new NoSuchNodeTypeException('nodeTypeName is <null>');
        }
        if ('' === $nodeTypeName) {
            throw new NoSuchNodeTypeException('nodeTypeName is empty string');
        }

        if ($nodeTypeName[0] === '{') {
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
     * {@inheritDoc}
     *
     * @api
     */
    public function hasNodeType($name)
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
     * {@inheritDoc}
     *
     * @api
     */
    public function getAllNodeTypes()
    {
        $this->fetchNodeTypes();

        return new ArrayIterator(array_merge($this->primaryTypes, $this->mixinTypes));
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getPrimaryNodeTypes()
    {
        $this->fetchNodeTypes();

        return new ArrayIterator($this->primaryTypes);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getMixinNodeTypes()
    {
        $this->fetchNodeTypes();

        return new ArrayIterator($this->mixinTypes);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function createNodeTypeTemplate($ntd = null)
    {
       return $this->factory->get('NodeType\\NodeTypeTemplate', array($this, $ntd));
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function createNodeDefinitionTemplate()
    {
       return $this->factory->get('NodeType\\NodeDefinitionTemplate', array($this));
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function createPropertyDefinitionTemplate()
    {
       return $this->factory->get('NodeType\\PropertyDefinitionTemplate', array($this));
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function registerNodeType(NodeTypeDefinitionInterface $ntd, $allowUpdate)
    {
        self::registerNodeTypes(array($ntd), $allowUpdate);

        return each($ntd);
    }

    /**
     * Internally create a node type object
     *
     * @param NodeTypeDefinitionInterface $ntd
     * @param bool                        $allowUpdate whether updating the definition is to be allowed or not
     *
     * @return NodeType the new node type
     *
     * @throws \PHPCR\NodeType\NodeTypeExistsException
     */
    protected function createNodeType(NodeTypeDefinitionInterface $ntd, $allowUpdate)
    {
        if ($this->hasNodeType($ntd->getName()) && !$allowUpdate) {
            throw new NodeTypeExistsException('NodeType already existing: '.$ntd->getName());
        }

        return $this->factory->get('NodeType\\NodeType', array($this, $ntd));
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function registerNodeTypes(array $definitions, $allowUpdate)
    {
        $nts = array();
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

        return new ArrayIterator($nts);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function registerNodeTypesCnd($cnd, $allowUpdate)
    {
        //set fetched from backend to false to allow to load the new types from backend
        $fetched = $this->fetchedAllFromBackend;
        $this->fetchedAllFromBackend = false;
        $this->objectManager->registerNodeTypesCnd($cnd, $allowUpdate);

        //parse out type names and fetch types to return definitions of the new nodes
        preg_match_all('/\[([^\]]*)\]/', $cnd, $names);
        $types = array();
        foreach ($names[1] as $name) {
            $types[$name] = $this->getNodeType($name);
        }
        $this->fetchedAllFromBackend = $fetched;

        return $types;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function unregisterNodeType($name)
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
     * {@inheritDoc}
     *
     * @api
     */
    public function unregisterNodeTypes(array $names)
    {
        foreach ($names as $name) {
            $this->unregisterNodeType($name);
        }
    }

    /**
     * Provide Traversable interface: redirect to getAllNodeTypes
     *
     * @return Iterator over all node types
     */
    public function getIterator()
    {
        return $this->getAllNodeTypes();
    }
}
