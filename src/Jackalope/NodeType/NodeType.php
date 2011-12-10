<?php

namespace Jackalope\NodeType;

use ArrayIterator;
use Exception;

use PHPCR\PropertyType;
use PHPCR\ValueFormatException;
use PHPCR\NodeType\NodeTypeInterface;

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
class NodeType extends NodeTypeDefinition implements NodeTypeInterface
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

    /**
     * {@inheritDoc}
     *
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

    /**
     * {@inheritDoc}
     *
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

    /**
     * {@inheritDoc}
     *
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

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getSubtypes()
    {
        return new ArrayIterator($this->nodeTypeManager->getSubtypes($this->name));
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getDeclaredSubtypes()
    {
        return new ArrayIterator($this->nodeTypeManager->getDeclaredSubtypes($this->name));
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isNodeType($nodeTypeName)
    {
        return $this->getName() == $nodeTypeName || in_array($nodeTypeName, $this->getSupertypeNames());
    }

    /**
     * {@inheritDoc}
     *
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

    /**
     * {@inheritDoc}
     *
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

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function canSetProperty($propertyName, $value)
    {
        $propDefs = $this->getPropertyDefinitions();
        try {
            $type = PropertyType::determineType($value);
        } catch (ValueFormatException $e) {
            return false;
        }

        // check explicit matches first and keep wildcard definitions for later
        $wildcards = array();
        foreach ($propDefs as $prop) {
            if ('*' == $prop->getName()) {
                $wildcards[] = $prop;
            } elseif ($propertyName == $prop->getName()) {
                if (PropertyType::UNDEFINED == $prop->getRequiredType()
                    || $type == $prop->getRequiredType()
                ) {
                    return true;
                }
                // try if we can convert. OPTIMIZE: would be nice to know without actually attempting to convert
                try {
                    PropertyType::convertType($value, $prop->getRequiredType(), $type);
                    return true;
                } catch (ValueFormatException $e) {
                    // fall through and return false
                }
                return false; // if there is an explicit match, it has to fit
            }
        }
        // now check if any of the wildcards matches
        foreach ($wildcards as $prop) {
            if (PropertyType::UNDEFINED == $prop->getRequiredType()
                || $type == $prop->getRequiredType()
            ) {
                return true;
            }
            // try if we can convert. OPTIMIZE: would be nice to know without actually attempting to convert
            try {
                PropertyType::convertType($value, $prop->getRequiredType(), $type);
                return true;
            } catch (ValueFormatException $e) {
                return false; // if there is an explicit match, it has to fit
            }
        }
        return false;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function canAddChildNode($childNodeName, $nodeTypeName = null)
    {
        $childDefs = $this->getChildNodeDefinitions();
        if ($nodeTypeName) {
            try {
                $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName);
                if ($nodeType->isMixin() || $nodeType->isAbstract()) {
                    return false;
                }
            } catch (Exception $e) {
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

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function canRemoveNode($nodeName)
    {
        $childDefs = $this->getChildNodeDefinitions();
        foreach ($childDefs as $child) {
            if ($nodeName == $child->getName() &&
                ( $child->isMandatory() || $child->isProtected() )
            ) {
                return false;
            }
        }
        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function canRemoveProperty($propertyName)
    {
        $propDefs = $this->getPropertyDefinitions();
        foreach ($propDefs as $prop) {
            if ($propertyName == $prop->getName() &&
                ( $prop->isMandatory() || $prop->isProtected() )
            ) {
                return false;
            }
        }
        return true;
    }
}
