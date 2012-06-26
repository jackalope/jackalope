<?php

namespace Jackalope;

use ArrayIterator;
use IteratorAggregate;
use Exception;
use InvalidArgumentException;
use LogicException;

use PHPCR\PropertyType;
use PHPCR\PropertyInterface;
use PHPCR\NamespaceException;
use PHPCR\NodeInterface;
use PHPCR\NodeType\ConstraintViolationException;
use PHPCR\RepositoryException;
use PHPCR\PathNotFoundException;
use PHPCR\ItemNotFoundException;
use PHPCR\InvalidItemStateException;
use PHPCR\ItemExistsException;

use Jackalope\Factory;

/**
 * The Node interface represents a node in a workspace.
 *
 * You can iterate over the nodes children because it is an IteratorAggregate
 *
 * @api
 */
class Node extends Item implements IteratorAggregate, NodeInterface
{
    /**
     * The index if this is a same-name sibling.
     *
     * TODO: fully implement same-name siblings
     * @var int
     */
    protected $index = 1;

    /**
     * The primary type name of this node
     * @var string
     */
    protected $primaryType;

    /**
     * mapping of property name to PropertyInterface objects.
     *
     * all properties are instantiated in the constructor
     *
     * OPTIMIZE: lazy instantiate property objects, just have local array of values
     *
     * @var array
     */
    protected $properties = array();

    /**
     * keep track of properties to be deleted until the save operation was successful.
     *
     * this is needed in order to track deletions in case of refresh
     *
     * keys are the property names, values the properties (in state deleted)
     */
    protected $deletedProperties = array();

    /**
     * ordered list of the child node names
     *
     * @var array
     */
    protected $nodes = array();

    /**
     * ordered list of the child node names as known to be at the backend
     *
     * used to calculate reordering operations if orderBefore() was used
     *
     * @var array
     */
    protected $originalNodesOrder = null;

    /**
     * Create a new node instance with data from the storage layer
     *
     * This is only to be called by the Factory::get() method even inside the
     * Jackalope implementation to allow for custom implementations of Nodes.
     *
     * @param FactoryInterface $factory the object factory
     * @param array $rawData in the format as returned from
     *      \Jackalope\Transport\TransportInterface::getNode
     * @param string $path the absolute path of this node
     * @param Session $session
     * @param ObjectManager $objectManager
     * @param boolean $new set to true if this is a new node being created.
     *      Defaults to false which means the node is loaded from storage.
     *
     * @see \Jackalope\Transport\TransportInterface::getNode()
     *
     * @private
     */
    public function __construct(Factory $factory, $rawData, $path, Session $session, ObjectManager $objectManager, $new = false)
    {
        parent::__construct($factory, $path, $session, $objectManager, $new);
        $this->isNode = true;

        $this->parseData($rawData, false);
    }

    /**
     * Initialize or update this object with raw data from backend.
     *
     * @param array $rawData in the format as returned from Jackalope\Transport\TransportInterface
     * @param boolean $update whether to initialize this object or update
     * @param boolean $keepChanges only used if $update is true, same as $keepChanges in refresh()
     *
     * @see Node::__construct()
     * @see Node::refresh()
     */
    private function parseData($rawData, $update, $keepChanges = false)
    {
        //TODO: refactor to use hash array instead of stdClass struct

        if ($update) {
            // keep backup of old state so we can remove what needs to be removed
            $oldNodes = array_flip(array_values($this->nodes));
            $oldProperties = $this->properties;
        }
        /*
         * we collect all nodes coming from the backend. if we update with
         * $keepChanges, we use this to update the node list rather than losing
         * reorders
         *
         * properties are easy as they are not ordered.
         */
        $nodesInBackend = array();

        foreach ($rawData as $key => $value) {
            $node = false; // reset to avoid trouble
            if (is_object($value)) {
                // this is a node. add it if
                if (! $update || // init new node
                    ! $keepChanges || // want to discard changes
                    isset($oldNodes[$key]) || // it was already existing before reloading
                    ! ($node = $this->objectManager->getCachedNode($this->path . '/' . $key)) // we know nothing aobut it
                ) {
                    // for all those cases, if the node was moved away or is deleted in current session, we do not add it
                    if (! $this->objectManager->isNodeMoved($this->path . '/' . $key) &&
                        ! $this->objectManager->isItemDeleted($this->path . '/' . $key)
                    ) {
                        // otherwise we (re)load a node from backend but a child has been moved away already
                        $nodesInBackend[] = $key;
                    }
                }
                if ($update) {
                    unset($oldNodes[$key]);
                }
            } else {
                //property or meta information

                /* Property type declarations start with :, the value then is
                 * the type string from the NodeType constants. We skip that and
                 * look at the type when we encounter the value of the property.
                 *
                 * If its a binary data, we only get the type declaration and
                 * no data. Then the $value of the type declaration is not the
                 * type string for binary, but the number of bytes of the
                 * property - resp. array of number of bytes.
                 *
                 * The magic property ::NodeIteratorSize tells this node has no
                 * children. Ignore that info for now. We might optimize with
                 * this info once we do prefetch nodes.
                 */
                if (0 === strpos($key, ':')) {
                    if ((is_int($value) || is_array($value))
                         && $key != '::NodeIteratorSize'
                    ) {
                        // This is a binary property and we just got its length with no data
                        $key = substr($key, 1);
                        if (!isset($rawData->$key)) {
                            $binaries[$key] = $value;
                            if ($update) {
                                unset($oldProperties[$key]);
                            }
                            if (isset($this->properties[$key])) {
                                // refresh existing binary, this will only happen in update
                                // only update length
                                if (! ($keepChanges && $this->properties[$key]->isModified())) {
                                    $this->properties[$key]->_setLength($value);
                                    if ($this->properties[$key]->isDirty()) {
                                        $this->properties[$key]->setClean();
                                    }
                                }
                            } else {
                                // this will always fall into the creation mode
                                $this->_setProperty($key, $value, PropertyType::BINARY, true);
                            }
                        }
                    } //else this is a type declaration

                    //skip this entry (if its binary, its already processeed
                    continue;
                }

                if ($update && array_key_exists($key, $this->properties)) {
                    unset($oldProperties[$key]);
                    $prop = $this->properties[$key];
                    if ($keepChanges && $prop->isModified()) {
                        continue;
                    }
                } elseif ($update && array_key_exists($key, $this->deletedProperties)) {
                    if ($keepChanges) {
                        // keep the delete
                        continue;
                    } else {
                        // restore the property
                        $this->properties[$key] = $this->deletedProperties[$key];
                        $this->properties[$key]->setClean();
                        // now let the loop update the value. no need to talk to ObjectManager as it
                        // does not store property deletions
                    }
                }

                switch ($key) {
                    case 'jcr:index':
                        $this->index = $value;
                        break;
                    case 'jcr:primaryType':
                        $this->primaryType = $value;
                        // type information is exposed as property too, although there exist more specific methods
                        $this->_setProperty('jcr:primaryType', $value, PropertyType::NAME, true);
                        break;
                    case 'jcr:mixinTypes':
                        // type information is exposed as property too, although there exist more specific methods
                        $this->_setProperty($key, $value, PropertyType::NAME, true);
                        break;

                    // OPTIMIZE: do not instantiate properties until needed
                    default:
                        if (isset($rawData->{':' . $key})) {
                            /*
                             * this is an inconsistency between jackrabbit and
                             * dbal transport: jackrabbit has type name, dbal
                             * delivers numeric type.
                             * we should eventually fix the format returned by
                             * transport and either have jackrabbit transport
                             * do the conversion or let dbal store a string
                             * value instead of numerical.
                             */
                            $type = is_numeric($rawData->{':' . $key})
                                    ? $rawData->{':' . $key}
                                    : PropertyType::valueFromName($rawData->{':' . $key});
                        } else {
                            $type = PropertyType::determineType(is_array($value) ? reset($value) : $value);
                        }
                        $this->_setProperty($key, $value, $type, true);
                        break;
                }
            }
        }

        if ($update) {
            if ($keepChanges) {
                // we keep changes. merge new nodes to the right place
                $previous = null;
                $newFromBackend = array_diff($nodesInBackend, array_intersect($this->nodes, $nodesInBackend));

                foreach ($newFromBackend as $name) {
                    $pos = array_search($name, $nodesInBackend);
                    if (is_array($this->originalNodesOrder)) {
                        // update original order to send the correct reorderings
                        array_splice($this->originalNodesOrder, $pos, 0, $name);
                    }
                    if ($pos === 0) {
                        array_unshift($this->nodes, $name);
                    } else {
                        // do we find the predecessor of the new node in the list?
                        $insert = array_search($nodesInBackend[$pos-1], $this->nodes);
                        if (false !== $insert) {
                            array_splice($this->nodes, $insert + 1, 0, $name);
                        } else {
                            // failed to find predecessor, add to the end
                            $this->nodes[] = $name;
                        }
                    }
                }
            } else {
                // discard changes, just overwrite node list
                $this->nodes = $nodesInBackend;
                $this->originalNodesOrder = null;
            }
            foreach ($oldProperties as $name => $property) {
                if (! ($keepChanges && ($property->isNew()))) {
                    // may not call remove(), we dont want another delete with the backend to be attempted
                    $this->properties[$name]->setDeleted();
                    unset($this->properties[$name]);
                }
            }

            // notify nodes that where not received again that they disappeared
            foreach ($oldNodes as $name => $index) {
                if ($this->objectManager->purgeDisappearedNode($this->path . '/' . $name, $keepChanges)) {
                    // drop, it was not a new child
                    if ($keepChanges) { // otherwise we overwrote $this->nodes with the backend
                        $id = array_search($name, $this->nodes);
                        if (false !== $id) {
                            unset($this->nodes[$id]);
                        }
                    }
                }
            }
        } else {
            // new node loaded from backend
            $this->nodes = $nodesInBackend;
        }
    }

    /**
     * Creates a new node at the specified $relPath
     *
     * {@inheritDoc}
     *
     * In Jackalope, the child node type definition is immediatly applied if no
     * primaryNodeTypeName is specified.
     *
     * The PathNotFoundException and ConstraintViolationException are thrown immediatly.
     * Version and Lock are delayed until save.
     *
     * @api
     */
    public function addNode($relPath, $primaryNodeTypeName = null)
    {
        $this->checkState();

        $ntm = $this->session->getWorkspace()->getNodeTypeManager();

        // are we not the immediate parent?
        if (strpos($relPath, '/') !== false) {
            // forward to real parent
            $parentPath = dirname($relPath);
            if ($parentPath === '\\') {
                $parentPath = '/';
            }
            try {
                $parentNode = $this->objectManager->getNode($parentPath, $this->path);
            } catch (ItemNotFoundException $e) {
                try {
                    //we have to throw a different exception if there is a property with that name than if there is nothing at the path at all. lets see if the property exists
                    $prop = $this->objectManager->getPropertyByPath($this->getChildPath($parentPath));
                    if (! is_null($prop)) {
                        throw new ConstraintViolationException('Not allowed to add a node below a property');
                    }
                } catch (ItemNotFoundException $e) {
                    //ignore to throw the PathNotFoundException below
                }

                throw new PathNotFoundException($e->getMessage(), $e->getCode(), $e);
            }
            return $parentNode->addNode(basename($relPath), $primaryNodeTypeName);
        }

        if (is_null($primaryNodeTypeName)) {
            if ($this->primaryType === 'rep:root') {
                $primaryNodeTypeName = 'nt:unstructured';
            } else {
                $type = $ntm->getNodeType($this->primaryType);
                $nodeDefinitions = $type->getChildNodeDefinitions();
                foreach ($nodeDefinitions as $def) {
                    if (!is_null($def->getDefaultPrimaryType())) {
                        $primaryNodeTypeName = $def->getDefaultPrimaryTypeName();
                        break;
                    }
                }
            }
            if (is_null($primaryNodeTypeName)) {
                throw new ConstraintViolationException("No matching child node definition found for `$relPath' in type `{$this->primaryType}'. Please specify the type explicitly.");
            }
        }

        // create child node
        //sanity check: no index allowed. TODO: we should verify this is a valid node name
        if (false !== strpos($relPath, ']')) {
            throw new RepositoryException("Index not allowed in name of newly created node: $relPath");
        }
        if (in_array($relPath, $this->nodes)) {
            throw new ItemExistsException("This node already has a child named $relPath."); //TODO: same-name siblings if nodetype allows for them
        }

        $data = array('jcr:primaryType' => $primaryNodeTypeName);
        $path = $this->getChildPath($relPath);
        $node = $this->factory->get('Node', array($data, $path, $this->session, $this->objectManager, true));

        $this->addChildNode($node, false); // no need to check the state, we just checked when entering this method
        $this->objectManager->addNode($path, $node);

        if (is_array($this->originalNodesOrder)) {
            // new nodes are added at the end
            $this->originalNodesOrder[] = $relPath;
        }
        //by definition, adding a node sets the parent to modified
        $this->setModified();

        return $node;
    }

    /**
     * Jackalope implements this feature and updates the position of the
     * existing child at srcChildRelPath to be in the list immediately before
     * destChildRelPath.
     *
     * {@inheritDoc}
     *
     * Jackalope has no implementation-specific ordering restriction so no
     * \PHPCR\ConstraintViolationException is expected. VersionException and
     * LockException are not tested immediatly but thrown on save.
     *
     * @api
     */
    public function orderBefore($srcChildRelPath, $destChildRelPath)
    {
        if ($srcChildRelPath == $destChildRelPath) {
            //nothing to move
            return;
        }

        if (null == $this->originalNodesOrder) {
            $this->originalNodesOrder = $this->nodes;
        }

        $this->nodes = $this->orderBeforeArray($srcChildRelPath, $destChildRelPath, $this->nodes);
        $this->setModified();
    }

   /**
    * Returns the orderBefore commands to be applied to the childnodes
    * to get from the original order to the new one
    *
    * Maybe this could be optimized, so that it needs less orderBefore
    *  commands on the backend
    *
    * @return array of arrays with 2 fields: name of node to order before second name
    *
    * @private
    */
    public function getOrderCommands()
    {
        $reorders = array();
        if (!$this->originalNodesOrder) {
            return $reorders;
        }

        //check for deleted nodes
        $newIndex = array_flip($this->nodes);

        foreach ($this->originalNodesOrder as $k => $v) {
            if (!isset($newIndex[$v])) {
                unset($this->orignalNodesOrder[$k]);
            }
        }

        // reindex the arrays to avoid holes in the indexes
        $this->originalNodesOrder = array_values($this->originalNodesOrder);
        $this->nodes = array_values($this->nodes);

        $len = count($this->nodes) - 1;
        $oldIndex = array_flip($this->originalNodesOrder);

        //go backwards on the new node order and arrange them this way
        for ($i = $len; $i >= 0; $i--) {
            //get the name of the child node
            $c = $this->nodes[$i];
            //check if it's not the last node
            if (isset($this->nodes[$i + 1])) {
                // get the name of the next node
                $next = $this->nodes[$i + 1];
                //if in the old order $c and next are not neighbors already, do the reorder command
                if ($oldIndex[$c] + 1 != $oldIndex[$next]) {
                    $reorders[] = array($c,$next);
                    $this->originalNodesOrder = $this->orderBeforeArray($c,$next,$this->originalNodesOrder);
                    $oldIndex = array_flip($this->originalNodesOrder);
                }
            } else {
                //check if it's not already at the end of the nodes
                if ($oldIndex[$c] != $len) {
                    $reorders[] = array($c,null);
                    $this->originalNodesOrder = $this->orderBeforeArray($c,null,$this->originalNodesOrder);
                    $oldIndex = array_flip($this->originalNodesOrder);
                }
            }
        }
        $this->originalNodesOrder = null;
        return $reorders;
    }

    /**
     * Perform the move operation
     *
     * @param string $srcChildRelPath name of the node to move
     * @param string $destChildRelPath name of the node srcChildRelPath has to be ordered before, null to move to the end
     * @param array $nodes the array of child nodes
     *
     * @return array The updated $nodes array with new order
     */
    protected function orderBeforeArray($srcChildRelPath, $destChildRelPath, $nodes)
    {
		$nodes = array_values($nodes);

        // search old position
        $srcPosition = array_search($srcChildRelPath, $nodes);
        if (false === $srcPosition) {
            throw new ItemNotFoundException("$srcChildRelPath is not a valid child of ".$this->path);
        }

        // search position to move before
        $destPosition = array_search($destChildRelPath, $nodes);
        if (false === $destPosition) {
            if (null === $destChildRelPath) {
                // To place the node srcChildRelPath at the end of the list, a destChildRelPath of null is used.
                $destPosition = count($nodes);
            }

            throw new ItemNotFoundException("$destChildRelPath is not a valid child of ".$this->path);
        }

		// sort nodes array (should this method be called "sort"?)
		uksort($nodes, function ($leftPosition, $rightPosition) use ($srcPosition, $destPosition) {
			if ($leftPosition == $srcPosition) {
				$leftPosition = $destPosition - 0.5;
			}

			if ($rightPosition == $srcPosition) {
				$rightPosition = $destPosition - 0.5;
			}

			if ($leftPosition == $rightPosition) {
				return 0;
			}

			return ($leftPosition < $rightPosition) ? -1 : 1;
		});

		return array_values($nodes);
    }

    /**
     * {@inheritDoc}
     *
     * @param boolean $validate does the NodeType control throw an exception if the property can't be set? To use in case of UUID import
     *
     * @api
     */
    public function setProperty($name, $value, $type = PropertyType::UNDEFINED, $validate = true)
    {
        $this->checkState();

        //$value is null for property removal
        if (!is_null($value)) {
            $nt = $this->getPrimaryNodeType();
            //will throw a ConstraintViolationException if this property can't be set
            $nt->canSetProperty($name, $value, $validate);
        }

        //try to get a namespace for the set property
        if (strpos($name, ':') !== false) {
            list($prefix) = explode(':', $name);
            //Check if the namespace exists. If not, throw an NamespaceException
            $this->session->getNamespaceURI($prefix);
        }

        if (is_null($value)) {
            if (isset($this->properties[$name])) {
                $this->properties[$name]->remove();
            }
            return null;
        }

        return $this->_setProperty($name, $value, $type, false);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getNode($relPath)
    {
        $this->checkState();

        if (strlen($relPath) == 0 || '/' == $relPath[0]) {
            throw new PathNotFoundException("$relPath is not a relative path");
        }

        try {
            $node = $this->objectManager->getNodeByPath($this->objectManager->absolutePath($this->path, $relPath));
        } catch (ItemNotFoundException $e) {
            throw new PathNotFoundException($e->getMessage(), $e->getCode(), $e);
        }
        return $node;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getNodes($filter = null)
    {
        $this->checkState();

        $names = self::filterNames($filter, $this->nodes);
        $result = array();
        if (!empty($names)) {
            foreach ($names as $name) {
                $paths[] = $this->objectManager->absolutePath($this->path, $name);
            }
            $nodes = $this->objectManager->getNodesByPath($paths);
            foreach ($nodes as $path => $node) {
                $result[basename($path)] = $node;
            }
        }
        /* FIXME: Actually, the whole list should be lazy loaded and maybe only fetch a
                   a few dozen child nodes at once. This approach here doesn't scale if you
                   have many many many child nodes
        */

        return new ArrayIterator($result);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getProperty($relPath)
    {
        $this->checkState();

        if (false === strpos($relPath, '/')) {
            if (!isset($this->properties[$relPath])) {
                throw new PathNotFoundException("Property $relPath in ".$this->path);
            }

            return $this->properties[$relPath];
        }

        return $this->session->getProperty($this->getChildPath($relPath));
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getPropertyValue($name, $type=null)
    {
        $this->checkState();

        $val = $this->getProperty($name)->getValue();
        if (! is_null($type)) {
            $val = PropertyType::convertType($val, $type);
        }
        return $val;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getProperties($filter = null)
    {
        $this->checkState();

        //OPTIMIZE: lazy iterator?
        $names = self::filterNames($filter, array_keys($this->properties));
        $result = array();
        foreach ($names as $name) {
            $result[$name] = $this->properties[$name]; //we know for sure the properties exist, as they come from the array keys of the array we are accessing
        }
        return new ArrayIterator($result);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getPropertiesValues($filter = null, $dereference = true)
    {
        $this->checkState();

        // OPTIMIZE: do not create properties in constructor, go over array here
        $names = self::filterNames($filter, array_keys($this->properties));
        $result = array();
        foreach ($names as $name) {
            //we know for sure the properties exist, as they come from the array keys of the array we are accessing
            $type = $this->properties[$name]->getType();
            if (! $dereference &&
                    (PropertyType::REFERENCE == $type
                    || PropertyType::WEAKREFERENCE == $type
                    || PropertyType::PATH == $type)
            ) {
                $result[$name] = $this->properties[$name]->getString();
            } else {
                // OPTIMIZE: collect the paths and call objectmanager->getNodesByPath once
                $result[$name] = $this->properties[$name]->getValue();
            }
        }
        return $result;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getPrimaryItem()
    {
        try {
            $primary_item = null;
            $item_name = $this->getPrimaryNodeType()->getPrimaryItemName();

            if ($item_name !== null) {
                $primary_item = $this->session->getItem($this->path . '/' . $item_name);
            }
        } catch (Exception $ex) {
            throw new RepositoryException("An error occured while reading the primary item of the node '{$this->path}': " . $ex->getMessage());
        }

        if ($primary_item === null) {
           throw new ItemNotFoundException("No primary item found for node '{$this->path}'");
        }

        return $primary_item;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getIdentifier()
    {
        $this->checkState();

        if (isset($this->properties['jcr:uuid'])) {
            return $this->getPropertyValue('jcr:uuid');
        }
        return null;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getIndex()
    {
        $this->checkState();

        return $this->index;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getReferences($name = null)
    {
        $this->checkState();

        return $this->objectManager->getReferences($this->path, $name);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getWeakReferences($name = null)
    {
        $this->checkState();

        return $this->objectManager->getWeakReferences($this->path, $name);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function hasNode($relPath)
    {
        $this->checkState();

        if (false === strpos($relPath, '/')) {
            return array_search($relPath, $this->nodes) !== false;
        }
        if (! strlen($relPath) || $relPath[0] == '/') {
            throw new InvalidArgumentException("'$relPath' is not a relative path");
        }

        return $this->session->nodeExists($this->getChildPath($relPath));
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function hasProperty($relPath)
    {
        $this->checkState();

        if (false === strpos($relPath, '/')) {
            return isset($this->properties[$relPath]);
        }
        if (! strlen($relPath) || $relPath[0] == '/') {
            throw new InvalidArgumentException("'$relPath' is not a relative path");
        }

        return $this->session->propertyExists($this->getChildPath($relPath));
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function hasNodes()
    {
        $this->checkState();

        return !empty($this->nodes);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function hasProperties()
    {
        $this->checkState();

        return (! empty($this->properties));
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getPrimaryNodeType()
    {
        $this->checkState();

        $ntm = $this->session->getWorkspace()->getNodeTypeManager();
        return $ntm->getNodeType($this->primaryType);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getMixinNodeTypes()
    {
        $this->checkState();

        if (!isset($this->properties['jcr:mixinTypes'])) {
            return array();
        }
        $res = array();
        $ntm = $this->session->getWorkspace()->getNodeTypeManager();
        foreach ($this->properties['jcr:mixinTypes']->getValue() as $type) {
            $res[] = $ntm->getNodeType($type);
        }
        return $res;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isNodeType($nodeTypeName)
    {
        $this->checkState();

        // is it the primary type?
        if ($this->primaryType == $nodeTypeName) {
            return true;
        }
        $ntm = $this->session->getWorkspace()->getNodeTypeManager();
        // is the primary type a subtype of the type?
        if ($ntm->getNodeType($this->primaryType)->isNodeType($nodeTypeName)) {
            return true;
        }
        // if there are no mixin types, then we now know this node is not of that type
        if (! isset($this->properties["jcr:mixinTypes"])) {
            return false;
        }
        // is it one of the mixin types?
        if (in_array($nodeTypeName, $this->properties["jcr:mixinTypes"]->getValue())) {
            return true;
        }
        // is it an ancestor of any of the mixin types?
        foreach ($this->properties['jcr:mixinTypes'] as $mixin) {
            if ($ntm->getNodeType($mixin)->isNodeType($nodeTypeName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Changes the primary node type of this node to nodeTypeName.
     *
     * {@inheritDoc}
     *
     * Jackalope only validates type conflicts on save.
     *
     * @api
     */
    public function setPrimaryType($nodeTypeName)
    {
        $this->checkState();

        throw new NotImplementedException('Write');
    }

    /**
     * {@inheritDoc}
     *
     * Jackalope validates type conflicts only on save, not immediatly.
     *It is possible to add mixin types after the first save.
     *
     * @api
     */
    public function addMixin($mixinName)
    {
        // Check if mixinName exists as a mixin type
        $typemgr = $this->session->getWorkspace()->getNodeTypeManager();
        $nodeType = $typemgr->getNodeType($mixinName);
        if (! $nodeType->isMixin()) {
            throw new ConstraintViolationException("Trying to add a mixin '$mixinName' that is a primary type");
        }

        $this->checkState();

        // TODO handle LockException & VersionException cases
        if ($this->hasProperty('jcr:mixinTypes')) {
            if (array_search($mixinName, $this->properties['jcr:mixinTypes']->getValue()) === false) {
                $this->properties['jcr:mixinTypes']->addValue($mixinName);
                $this->setModified();
            }
        } else {
            $this->setProperty('jcr:mixinTypes', array($mixinName), PropertyType::NAME);
            $this->setModified();
        }
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function removeMixin($mixinName)
    {
        $this->checkState();

        // check if node type is assigned

        $this->setModified();

        throw new NotImplementedException('Write');
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function canAddMixin($mixinName)
    {
        $this->checkState();

        throw new NotImplementedException('Write');
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDefinition()
    {
        $this->checkState();

        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function update($srcWorkspace)
    {
        $this->checkState();

        if ($this->isNew()) {
            //no node in workspace
            return;
        }

        throw new NotImplementedException('Write');
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getCorrespondingNodePath($workspaceName)
    {
        $this->checkState();

        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getSharedSet()
    {
        $this->checkState();

        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function removeSharedSet()
    {
        $this->checkState();
        $this->setModified();

        throw new NotImplementedException('Write');
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function removeShare()
    {
        $this->checkState();
        $this->setModified();

        throw new NotImplementedException('Write');
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isCheckedOut()
    {
        $this->checkState();

        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isLocked()
    {
        $this->checkState();

        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function followLifecycleTransition($transition)
    {
        $this->checkState();
        $this->setModified();

        throw new NotImplementedException('Write');
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAllowedLifecycleTransitions()
    {
        $this->checkState();

        throw new NotImplementedException('Write');
    }

    /**
     * Refresh this node
     *
     * {@inheritDoc}
     *
     * This is also called internally to refresh when the node is accessed in
     * state DIRTY.
     *
     * @param boolean $keepChanges whether to keep local changes
     * @param boolean $internal implementation internal flag to not check for the InvalidItemStateException
     *
     * @see Item::checkState
     *
     * @api
     */
    public function refresh($keepChanges, $internal = false)
    {
        if (! $internal && $this->isDeleted()) {
            throw new InvalidItemStateException('This item has been removed and can not be refreshed');
        }
        $deleted = false;

        // Get properties and children from backend
        try {
            $json = $this->objectManager->getTransport()->getNode(is_null($this->oldPath) ? $this->path : $this->oldPath);
        } catch (ItemNotFoundException $ex) {

            // The node was deleted in another session
            if (! $this->objectManager->purgeDisappearedNode($this->path, $keepChanges)) {
                throw new LogicException($this->path . " should be purged and not kept");
            }
            $keepChanges = false; // delete never keeps changes
            if (! $internal) {
                // this is not an internal update
                $deleted = true;
            }

            // continue with emtpy data, parseData will notify all cached
            // children and all properties that we are removed
            $json = array();
        }

        $this->parseData($json, true, $keepChanges);

        if ($deleted) {
            $this->setDeleted();
        }
    }

    /**
     * Remove this node
     *
     * {@inheritDoc}
     *
     * A jackalope node needs to notify the parent node about this if it is
     * cached, in addition to \PHPCR\ItemInterface::remove()
     *
     * @uses Node::unsetChildNode()
     *
     * @api
     */
    public function remove()
    {
        $this->checkState();
        $parent = $this->getParent();

        $parentNodeType = $parent->getPrimaryNodeType();
        //will throw a ConstraintViolationException if this node can't be removed
        $parentNodeType->canRemoveNode($this->getName(), true);

        if ($parent) {
            $parent->unsetChildNode($this->name, true);
        }
        // once we removed ourselves, $this->getParent() won't work anymore. do this last
        parent::remove();
    }

    /**
     * Removes the reference in the internal node storage
     *
     * @param string $name the name of the child node to unset
     * @param bool $check whether a state check should be done - set to false
     *      during internal update operations
     *
     * @return void
     *
     * @throws ItemNotFoundException If there is no child with $name
     *
     * @private
     */
    public function unsetChildNode($name, $check)
    {
        if ($check) {
            $this->checkState();
        }

        $key = array_search($name, $this->nodes);
        if ($key === false) {
            if (! $check) {
                // inside a refresh operation
                return;
            }
            throw new ItemNotFoundException("Could not remove child node because it's already gone");
        }

        unset($this->nodes[$key]);
    }

    /**
     * Adds child node to this node for internal reference
     *
     * @param string $name The name of the child node
     * @param boolean $check whether to check state
     * @param string $name is used in cases where $node->getName would not return the correct name (during move operation)
     *
     * @private
     */
    public function addChildNode(NodeInterface $node, $check, $name = null)
    {
        if ($check) {
            $this->checkState();
        }

        if (is_null($name)) {
            $name = $node->getName();
        }

        $nt = $this->getPrimaryNodeType();
        //will throw a ConstraintViolationException if this node can't be added
        $nt->canAddChildNode($name, $node->getPrimaryNodeType()->getName(), true);

        // TODO: same name siblings

        $this->nodes[] = $name;
    }

    /**
     * Removes the reference in the internal node storage
     *
     * @param string $name the name of the property to unset.
     *
     * @return void
     *
     * @throws ItemNotFoundException If this node has no property with name $name
     *
     * @private
     */
    public function unsetProperty($name)
    {
        $this->checkState();
        $this->setModified();

        if (!array_key_exists($name, $this->properties)) {
            throw new ItemNotFoundException('Implementation Error: Could not remove property from node because it is already gone');
        }
        $this->deletedProperties[$name] = $this->properties[$name];
        unset($this->properties[$name]);
    }

    /**
     * In addition to calling parent method, tell all properties and clean deletedProperties
     */
    public function confirmSaved()
    {
        foreach ($this->properties as $property) {
            if ($property->isModified() || $property->isNew()) {
                $property->confirmSaved();
            }
        }
        $this->deletedProperties = array();
        parent::confirmSaved();
    }

    /**
     * In addition to calling parent method, tell all properties
     */
    public function setPath($path, $move = false)
    {
        parent::setPath($path, $move);
        foreach ($this->properties as $property) {
            $property->setPath($path.'/'.basename($property->getPath()), $move);
        }
    }

    /**
     * Make sure $p is an absolute path
     *
     * If its a relative path, prepend the path to this node, otherwise return as is
     *
     * @param string $p the relative or absolute property or node path
     *
     * @return string the absolute path to this item, with relative paths resolved against the current node
     */
    protected function getChildPath($p)
    {
        if ('' == $p) {
            throw new InvalidArgumentException("Name can not be empty");
        }
        if ($p[0] == '/') {
            return $p;
        }
        //relative path, combine with base path for this node
        $path = $this->path === '/' ? '/' : $this->path.'/';
        return $path . $p;
    }

    /**
     * Filter the list of names according to the filter expression / array
     *
     * @param string|array $filter according to getNodes|getProperties
     * @param array $names list of names to filter
     *
     * @return the names in $names that match a filter
     */
    protected static function filterNames($filter, $names)
    {
        if (is_string($filter)) {
            $filter = explode('|', $filter);
        }
        $filtered = array();
        if ($filter !== null) {
            foreach ($filter as $k => $f) {
               $f = trim($f);
               $filter[$k] = strtr($f, array('*'=>'.*', //wildcard
                                             '.'  => '\\.', //escape regexp
                                             '\\' => '\\\\',
                                             '{'  => '\\{',
                                             '}'  => '\\}',
                                             '('  => '\\(',
                                             ')'  => '\\)',
                                             '+'  => '\\+',
                                             '^'  => '\\^',
                                             '$'  => '\\$'));
            }
            foreach ($names as $name) {
                foreach ($filter as $f) {
                    if (preg_match('/^'.$f.'$/', $name)) {
                        $filtered[] = $name;
                    }
                }
            }
        } else {
            $filtered = $names;
        }
        return $filtered;
    }

    /**
     * Provide Traversable interface: redirect to getNodes with no filter
     *
     * @return Iterator over all child nodes
     */
    public function getIterator()
    {
        $this->checkState();

        return $this->getNodes();
    }

    /**
     * Implement really setting the property without any notification.
     *
     * Implement the setProperty, but also used from constructor or in refresh,
     * when the backend has a new property that is not yet loaded in memory.
     *
     * @param string $name
     * @param mixed $value
     * @param string $type
     * @param boolean $internal whether we are setting this node through api or internally
     *
     * @return Property
     *
     * @see Node::setProperty
     * @see Node::refresh
     * @see Node::__construct
     */
    protected function _setProperty($name, $value, $type, $internal)
    {
        if ($name == '' | false !== strpos($name, '/')) {
            throw new InvalidArgumentException("The name '$name' is no valid property name");
        }

        if (!isset($this->properties[$name])) {
            $path = $this->getChildPath($name);
            $property = $this->factory->get(
                            'Property',
                            array(array('type' => $type, 'value' => $value),
                                  $path,
                                  $this->session,
                                  $this->objectManager,
                                  ! $internal));
            $this->properties[$name] = $property;
            if (! $internal) {
                $this->setModified();
            }
        } else {
            if ($internal) {
                $this->properties[$name]->_setValue($value, $type);
                if ($this->properties[$name]->isDirty()) {
                    $this->properties[$name]->setClean();
                }
            } else {
                $this->properties[$name]->setValue($value, $type);
            }
        }
        return $this->properties[$name];
    }

    /**
     * In addition to set this item deleted, set all properties to deleted.
     *
     * They will be automatically deleted by the backend, but the user might
     * still have a reference to one of the property objects.
     */
    public function setDeleted()
    {
        parent::setDeleted();
        foreach ($this->properties as $property) {
            $property->setDeleted(); // not all properties are tracked in objectmanager
        }
    }

    /**
     * {@inheritDoc}
     *
     * Additionally, notifies all properties of this node. Child nodes are not
     * notified, it is the job of the ObjectManager to know which nodes are
     * cached and notify them.
     */
    public function beginTransaction()
    {
        parent::beginTransaction();

        // Notify the children properties
        foreach ($this->properties as $prop) {
            $prop->beginTransaction();
        }
    }

    /**
     * {@inheritDoc}
     *
     * Additionally, notifies all properties of this node. Child nodes are not
     * notified, it is the job of the ObjectManager to know which nodes are
     * cached and notify them.
     */
    public function commitTransaction()
    {
        parent::commitTransaction();

        foreach ($this->properties as $prop) {
            $prop->commitTransaction();
        }
    }

    /**
     * {@inheritDoc}
     *
     * Additionally, notifies all properties of this node. Child nodes are not
     * notified, it is the job of the ObjectManager to know which nodes are
     * cached and notify them.
     */
    public function rollbackTransaction()
    {
        parent::rollbackTransaction();

        foreach ($this->properties as $prop) {
            $prop->rollbackTransaction();
        }
    }
}
