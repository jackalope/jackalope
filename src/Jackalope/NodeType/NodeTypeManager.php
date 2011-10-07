<?php
namespace Jackalope\NodeType;

use Jackalope\ObjectManager, Jackalope\NotImplementedException;
use ArrayIterator;

/**
 * {@inheritDoc}
 *
 * In Jackalope, we try to do lazy fetching of node types to reduce overhead.
 * Jackalope supports registering node types, and when using the jackrabbit for
 * transport, there is an additional method registerNodeTypesCnd for the
 * jackrabbit specific textual node type specification
 */
class NodeTypeManager implements \IteratorAggregate, \PHPCR\NodeType\NodeTypeManagerInterface
{
    /**
     * The factory to instantiate objects.
     * @var \Jackalope\Factory
     */
    protected $factory;
    /**
     * @var \Jackalope\ObjectManager
    protected $objectManager;

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
     * @param object $factory an object factory implementing "get" as
     *      described in \Jackalope\Factory
     * @param ObjectManager $objectManager
     */
    public function __construct($factory, ObjectManager $objectManager)
    {
        $this->factory = $factory;
        $this->objectManager = $objectManager;
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
     *
     * @return void
     */
    protected function fetchNodeTypes($name = null)
    {
        if ($this->fetchedAllFromBackend) {
            return;
        }

        if (! is_null($name)) {
            if (empty($this->primaryTypes[$name])
                && empty($this->mixinTypes[$name])
            ) {
                //OPTIMIZE: also avoid trying to fetch nonexisting definitions we already tried to get
                $nodetypes = $this->objectManager->getNodeType($name);
            } else {
                return; //we already know this node
            }
        } else {
            $nodetypes = $this->objectManager->getNodeTypes();
            $this->fetchedAllFromBackend = true;
        }

        foreach ($nodetypes as $nodetype) {
            $nodetype = $this->factory->get('NodeType\NodeType', array($this, $nodetype));
            $name = $nodetype->getName();
            //do not overwrite existing types. maybe they where changed locally
            if (empty($this->primaryTypes[$name])
                && empty($this->mixinTypes[$name])
            ) {
                $this->addNodeType($nodetype);
            }
        }
    }

    /**
     * Stores the node type in our internal structures (flat && tree)
     *
     * @param \PHPCR\NodeType\NodeTypeInterface $nodetype The nodetype to add
     */
    protected function addNodeType(\PHPCR\NodeType\NodeTypeInterface $nodetype)
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
     * @param string Nodename
     *
     * @return array of strings with the names of the subnodes
     *
     * @private
     */
    public function getDeclaredSubtypes($nodeTypeName)
    {
        // TODO: do we need to call fetchNodeTypes(null) here?
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
     * @return array of strings with the names of the subnodes
     *
     * @private
     */
    public function getSubtypes($nodeTypeName)
    {
        $ret = array();
        if (empty($this->nodeTree[$nodeTypeName])) {
            return array();
        }

        foreach ($this->nodeTree[$nodeTypeName] as $subnode) {
            $ret = array_merge($ret, array($subnode), $this->getDeclaredSubtypes($subnode));
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
                $this->nodeTree[$declaredSupertypeName] = array_merge($this->nodeTree[$declaredSupertypeName], array($nodetype->getName()));
            } else {
                $this->nodeTree[$declaredSupertypeName] = array($nodetype->getName());
            }
        }
    }

    // inherit all doc
    /**
     * @api
     */
    public function getNodeType($nodeTypeName)
    {
        $this->fetchNodeTypes($nodeTypeName);

        if (isset($this->primaryTypes[$nodeTypeName])) {
            return $this->primaryTypes[$nodeTypeName];
        }
        if (isset($this->mixinTypes[$nodeTypeName])) {
            return $this->mixinTypes[$nodeTypeName];
        }
        if (is_null($nodeTypeName)) {
            $nodeTypeName = 'nodeTypeName was <null>';
        }
        throw new \PHPCR\NodeType\NoSuchNodeTypeException($nodeTypeName);
    }

    // inherit all doc
    /**
     * @api
     */
    public function hasNodeType($name)
    {
        $this->fetchNodeTypes($name);
        return isset($this->primaryTypes[$name]) || isset($this->mixinTypes[$name]);
    }

    // inherit all doc
    /**
     * @api
     */
    public function getAllNodeTypes()
    {
        $this->fetchNodeTypes();
        return new ArrayIterator(array_values(array_merge($this->primaryTypes, $this->mixinTypes)));
    }

    // inherit all doc
    /**
     * @api
     */
    public function getPrimaryNodeTypes()
    {
        $this->fetchNodeTypes();
        return new ArrayIterator(array_values($this->primaryTypes));
    }

    // inherit all doc
    /**
     * @api
     */
    public function getMixinNodeTypes()
    {
        $this->fetchNodeTypes();
        return new ArrayIterator(array_values($this->mixinTypes));
    }

    // inherit all doc
    /**
     * @api
     */
    public function createNodeTypeTemplate($ntd = null)
    {
       return $this->factory->get('NodeType\NodeTypeTemplate', array($this, $ntd));
    }

    // inherit all doc
    /**
     * @api
     */
    public function createNodeDefinitionTemplate()
    {
       return $this->factory->get('NodeType\NodeDefinitionTemplate', array($this));
    }

    // inherit all doc
    /**
     * @api
     */
    public function createPropertyDefinitionTemplate()
    {
       return $this->factory->get('NodeType\PropertyDefinitionTemplate', array($this));
    }

    // inherit all doc
    /**
     * @api
     */
    public function registerNodeType(\PHPCR\NodeType\NodeTypeDefinitionInterface $ntd, $allowUpdate)
    {
        self::registerNodeTypes(array($ntd), $allowUpdate);
        return each($ntd);
    }

    // inherit all doc
    /**
     * @api
     */
    protected function createNodeType(\PHPCR\NodeType\NodeTypeDefinitionInterface $ntd, $allowUpdate)
    {
        if ($this->hasNodeType($ntd->getName()) && !$allowUpdate) {
            throw new \PHPCR\NodeType\NodeTypeExistsException('NodeType already existing: '.$ntd->getName());
        }
        return $this->factory->get('NodeType\NodeType', array($this, $ntd));
    }

    // inherit all doc
    /**
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
     * Register namespaces and new node types or update node types based on a
     * jackrabbit cnd string
     *
     * From the Jackrabbit documentation:
     * The Compact Namespace and Node Type Definition (CND) notation provides
     * a compact standardized syntax for defining node types and making
     * namespace declarations.
     *
     * A simple example is
     *   <'phpcr'='http://www.doctrine-project.org/projects/phpcr_odm'>
     *   [phpcr:managed]
     *     mixin
     *     - phpcr:alias (string)
     *
     * For full documentation of the format, see
     * http://jackrabbit.apache.org/node-type-notation.html
     *
     * @param $cnd a string with cnd information.
     * @param boolean $allowUpdate whether to fail if node already exists or to
     *      update it.
     *
     * @return Iterator over the registered
     *      \PHPCR\NodeType\NodeTypeIteratorInterface implementing
     *      SeekableIterator and Countable. Keys are the node type names,
     *      values the corresponding NodeTypeInterface instances.
     *
     * @throws \PHPCR\InvalidNodeTypeDefinitionException if the
     *      NodeTypeDefinition is invalid.
     * @throws \PHPCR\NodeType\NodeTypeExistsException if allowUpdate is false
     *      and the NodeTypeDefinition specifies a node type name that is
     *      already registered.
     * @throws \PHPCR\UnsupportedRepositoryOperationException if this
     *      implementation does not support node type registration.
     * @throws \PHPCR\RepositoryException if another error occurs.
     *
     * @author david at liip.ch
     *
     * @private
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

    // inherit all doc
    /**
     * @api
     */
    public function unregisterNodeType($name)
    {
        if (!empty($this->primaryTypes[$name])) {
            unset($this->primaryTypes[$name]);
        } elseif (!empty($this->mixinTypes[$name])) {
            unset($this->mixinTypes[$name]);
        } else {
            throw new \PHPCR\NodeType\NoSuchNodeTypeException('NodeType not found: '.$name);
        }

        throw new NotImplementedException('TODO: remove from nodeTree and register with server (jackrabbit has not implemented this yet)');
    }

    // inherit all doc
    /**
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
