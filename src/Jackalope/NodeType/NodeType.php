<?php
namespace Jackalope\NodeType;

use ArrayIterator;
use Jackalope\NotImplementedException;

/**
 * {@inheritDoc}
 *
 * In Jackalope, the only information stored and thus available at
 * instantiation is the list of declared supertype names, child node type names
 * and property definition instances acquired from the NodeTypeDefinition.
 * All other information in this class is deduced from this when requested.
 *
 * @api
 */
class NodeType extends NodeTypeDefinition implements \PHPCR\NodeType\NodeTypeInterface
{
    /**
     * Cache of the declared super NodeType instances so they need to be
     * instantiated only once.
     *
     * @var array
     */
    protected $declaredSupertypes = null;
    /**
     * Cache of the aggregated super node type names so they need to be
     * aggregated only once.
     * @var array
     */
    protected $superTypeNames = null;
    /**
     * Cache of the aggregated super NodeType instances so they need to be
     * instantiated only once.
     */
    protected $superTypes = null;
    /**
     * Cache of the collected property definitions so they need to be
     * instantiated only once.
     * @var array
     */
    protected $propertyDefinitions = null;
    /**
     * Cache of the aggregated child node definitions from this type and all
     * its super type so they need to be gathered and instantiated only once.
     * @var array
     */
    protected $childNodeDefinitions = null;

    // inherit all doc
    /**
     * @api
     */
    public function getSupertypes()
    {
        if (null === $this->superTypes) {
            $this->superTypes = array();
            foreach ($this->getDeclaredSupertypes() as $superType) {
                $this->superTypes[] = $superType;
                $this->superTypes = array_merge($this->superTypes, $superType->getSupertypes());
            }
        }
        return $this->superTypes;
    }

    // inherit all doc
    /**
     * @api
     */
    protected function getSupertypeNames()
    {
        if (null === $this->superTypeNames) {
            $this->superTypeNames = array();
            foreach ($this->getSupertypes() as $superType) {
                $this->superTypeNames[] = $superType->getName();
            }
        }
        return $this->superTypeNames;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getDeclaredSupertypes()
    {
        if (null === $this->declaredSupertypes) {
            $this->declaredSupertypes = array();
            foreach ($this->declaredSuperTypeNames as $declaredSuperTypeName) {
                $this->declaredSupertypes[] = $this->nodeTypeManager->getNodeType($declaredSuperTypeName);
            }
        }
        return $this->declaredSupertypes;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getSubtypes()
    {
        return new \ArrayIterator($this->nodeTypeManager->getSubtypes($this->name));
    }

    // inherit all doc
    /**
     * @api
     */
    public function getDeclaredSubtypes()
    {
        return new \ArrayIterator($this->nodeTypeManager->getDeclaredSubtypes($this->name));
    }

    // inherit all doc
    /**
     * @api
     */
    public function isNodeType($nodeTypeName)
    {
        return $this->getName() == $nodeTypeName || in_array($nodeTypeName, $this->getSupertypeNames());
    }

    // inherit all doc
    /**
     * @api
     */
    public function getPropertyDefinitions()
    {
        if (null === $this->propertyDefinitions) {
            $this->propertyDefinitions = $this->getDeclaredPropertyDefinitions();
            foreach ($this->getSupertypes() as $nodeType) {
                $this->propertyDefinitions = array_merge($this->propertyDefinitions, $nodeType->getDeclaredPropertyDefinitions());
            }
        }
        return $this->propertyDefinitions;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getChildNodeDefinitions()
    {
        if (null === $this->childNodeDefinitions) {
            $this->childNodeDefinitions = $this->getDeclaredChildNodeDefinitions();
            foreach ($this->getSupertypes() as $nodeType) {
                $this->childNodeDefinitions = array_merge($this->childNodeDefinitions, $nodeType->getDeclaredChildNodeDefinitions());
            }
        }
        return $this->childNodeDefinitions;
    }

    // inherit all doc
    /**
     * @api
     */
    public function canSetProperty($propertyName, $value)
    {
        throw new NotImplementedException();
        // TODO: we need find a property definition that defines that name or allows * and check if it
        // requires a type that $value can be converted into
    }

    // inherit all doc
    /**
     * @api
     */
    public function canAddChildNode($childNodeName, $nodeTypeName = null)
    {
        $childDefs = $this->getChildNodeDefinitions();
        if ($nodeTypeName) {
            try {
                $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName);
            } catch (\Exception $e) {
                return false;
            }
        }
        foreach ($childDefs as $child) {
            if ( '*' == $child->getName() || $childNodeName == $child->getName()) {
                if ($nodeTypeName == null) {
                    if ($child->getDefaultPrimaryTypeName() != null) {
                        return true;
                    }
                } else {
                    foreach ($child->getRequiredPrimaryTypeNames() as $type) {
                        if ($nodeType->isNodeType($type)) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    // inherit all doc
    /**
     * @api
     */
    public function canRemoveNode($nodeName)
    {
        $childDefs = $this->getChildNodeDefinitions();
        foreach ($childDefs as $child) {
            if ($nodeName == $child->getName() && $child->isMandatory()) {
                return false;
            }
        }
        return true;
    }

    // inherit all doc
    /**
     * @api
     */
    public function canRemoveProperty($propertyName)
    {
        throw new NotImplementedException();
        // TODO: we need to check all property definitions if they require this property
    }
}
