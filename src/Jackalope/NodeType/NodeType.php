<?php
namespace Jackalope\NodeType;

use ArrayIterator;

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
        $ret = array();
        foreach ($this->nodeTypeManager->getSubtypes($this->name) as $subtype) {
            $ret[] = $this->nodeTypeManager->getNodeType($subtype);
        }
        return new ArrayIterator($ret);
    }

    // inherit all doc
    /**
     * @api
     */
    public function getDeclaredSubtypes()
    {
        $ret = array();
        foreach ($this->nodeTypeManager->getDeclaredSubtypes($this->name) as $subtype) {
            $ret[] = $this->nodeTypeManager->getNodeType($subtype);
        }
        return new ArrayIterator($ret);
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
    }

    // inherit all doc
    /**
     * @api
     */
    public function canAddChildNode($childNodeName, $nodeTypeName = null)
    {
        throw new NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    public function canRemoveNode($nodeName)
    {
        throw new NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    public function canRemoveProperty($propertyName)
    {
        throw new NotImplementedException();
    }
}
