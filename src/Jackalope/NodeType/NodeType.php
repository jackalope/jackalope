<?php
namespace Jackalope\NodeType;

use ArrayIterator;

/**
 * A NodeType object represents a "live" node type that is registered in the repository.
 */
class NodeType extends NodeTypeDefinition implements \PHPCR\NodeType\NodeTypeInterface
{
    protected $declaredSupertypes = null;
    protected $superTypeNames = null;
    protected $superTypes = null;

    protected $propertyDefinitions = null;
    protected $childNodeDefinitions = null;

    /**
     * Returns all supertypes of this node type in the node type inheritance
     * hierarchy. For primary types apart from nt:base, this list will always
     * include at least nt:base. For mixin types, there is no required supertype.
     *
     * @return array of \PHPCR\NodeType\NodeType objects.
     */
    public function getSupertypes()
    {
        if (null === $this->superTypes) {
            $this->superTypes = array();
            foreach ($this->getDeclaredSupertypes() as $superType) {
                array_push($this->superTypes, $superType);
                $this->superTypes = array_merge($this->superTypes, $superType->getSupertypes());
            }
        }
        return $this->superTypes;
    }

    /**
     * Returns all names of the supertypes
     *
     * @return array of strings with names of the supertypes
     */
     protected function getSupertypeNames()
     {
         if (null === $this->superTypeNames) {
             $this->superTypeNames = array();
             foreach ($this->getSupertypes() as $superType) {
                 array_push($this->superTypeNames, $superType->getName());
             }
        }
        return $this->superTypeNames;
     }

    /**
     * Returns the direct supertypes of this node type in the node type
     * inheritance hierarchy, that is, those actually declared in this node
     * type. In single-inheritance systems, this will always be an array of
     * size 0 or 1. In systems that support multiple inheritance of node
     * types this array may be of size greater than 1.
     *
     * @return array of \PHPCR\NodeType\NodeType objects.
     */
    public function getDeclaredSupertypes()
    {
        if (null === $this->declaredSupertypes) {
            $this->declaredSupertypes = array();
            foreach ($this->declaredSuperTypeNames as $declaredSuperTypeName) {
                array_push($this->declaredSupertypes, $this->nodeTypeManager->getNodeType($declaredSuperTypeName));
            }
        }
        return $this->declaredSupertypes;
    }

    /**
     * Returns all subtypes of this node type in the node type inheritance
     * hierarchy.
     *
     * @see getDeclaredSubtypes()
     *
     * @return \PHPCR\NodeType\NodeTypeIteratorInterface a NodeTypeIterator.
     */
    public function getSubtypes()
    {
        $ret = array();
        foreach ($this->nodeTypeManager->getSubtypes($this->name) as $subtype) {
            array_push($ret, $this->nodeTypeManager->getNodeType($subtype));
        }
        return new ArrayIterator($ret);
    }

    /**
     * Returns the direct subtypes of this node type in the node type inheritance
     * hierarchy, that is, those which actually declared this node type in their
     * list of supertypes.
     *
     * @see getSubtypes()
     *
     * @return \PHPCR\NodeType\NodeTypeIteratorInterface a NodeTypeIterator.
     */
    public function getDeclaredSubtypes()
    {
        $ret = array();
        foreach ($this->nodeTypeManager->getDeclaredSubtypes($this->name) as $subtype) {
            array_push($ret, $this->nodeTypeManager->getNodeType($subtype));
        }
        return new ArrayIterator($ret);
    }

    /**
     * Returns true if the name of this node type or any of its direct or
     * indirect supertypes is equal to nodeTypeName, otherwise returns false.
     *
     * @param string $nodeTypeName the name of a node type.
     * @return boolean
     */
    public function isNodeType($nodeTypeName)
    {
        return $this->getName() == $nodeTypeName || in_array($nodeTypeName, $this->getSupertypeNames());
    }

    /**
     * Returns an array containing the property definitions of this node
     * type. This includes both those property definitions actually declared
     * in this node type and those inherited from the supertypes of this type.
     *
     * @return array of \PHPCR\NodeType\PropertyDefinition containing the property definitions.
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

    /**
     * Returns an array containing the child node definitions of this node type.
     * This includes both those child node definitions actually declared in this
     * node type and those inherited from the supertypes of this node type.
     *
     * @return array an array of \PHPCR\NodeType\NodeDefinition containing the child node definitions.
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

    /**
     * Returns true if setting propertyName to value is allowed by this node type.
     * Otherwise returns false.
     *
     * @param string $propertyName The name of the property
     * @param mixed $value A variable or an array of variables
     * @return boolean
     */
    public function canSetProperty($propertyName, $value)
    {
        throw new NotImplementedException();
    }

    /**
     * Returns true if this node type allows the addition of a child node called
     * childNodeName without specific node type information (that is, given the
     * definition of this parent node type, the child node name is sufficient to
     * determine the intended child node type). Returns false otherwise.
     * If $nodeTypeName is given returns true if this node type allows the
     * addition of a child node called childNodeName of node type nodeTypeName.
     * Returns false otherwise.
     *
     * @param string $childNodeName The name of the child node.
     * @param string $nodeTypeName The name of the node type of the child node.
     * @return boolean
     */
    public function canAddChildNode($childNodeName, $nodeTypeName = NULL)
    {
        throw new NotImplementedException();
    }

    /**
     * Returns true if removing the child node called nodeName is allowed by this
     * node type. Returns false otherwise.
     *
     * @param string $nodeName The name of the child node
     * @return boolean
     */
    public function canRemoveNode($nodeName)
    {
        throw new NotImplementedException();
    }

    /**
     * Returns true if removing the property called propertyName is allowed by this
     * node type. Returns false otherwise.
     *
     * @param string $propertyName The name of the property
     * @return boolean
     */
    public function canRemoveProperty($propertyName)
    {
        throw new NotImplementedException();
    }
}
