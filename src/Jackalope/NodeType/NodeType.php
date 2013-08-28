<?php

namespace Jackalope\NodeType;

use ArrayIterator;
use Exception;

use PHPCR\PropertyType;
use PHPCR\ValueFormatException;
use PHPCR\NodeType\NodeTypeInterface;
use PHPCR\NodeType\ConstraintViolationException;
use PHPCR\NodeType\NoSuchNodeTypeException;

/**
 * {@inheritDoc}
 *
 * In Jackalope, the only information stored and thus available at
 * instantiation is the list of declared supertype names, child node type names
 * and property definition instances acquired from the NodeTypeDefinition.
 * All other information in this class is deduced from this when requested.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
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
    public function getSupertypeNames()
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
    public function canSetProperty($propertyName, $value, $throw = false)
    {
        $propDefs = $this->getPropertyDefinitions();
        try {
            $type = $this->valueConverter->determineType(is_array($value) ? reset($value) : $value);
        } catch (ValueFormatException $e) {
            if ($throw) {
                throw new ValueFormatException($propertyName.': '.$e->getMessage(), $e->getCode(), $e);
            }

            return false;
        }

        // check explicit matches first and keep wildcard definitions for later

        $wildcards = array();
        foreach ($propDefs as $prop) {
            if ('*' == $prop->getName()) {
                $wildcards[] = $prop;
            } elseif ($propertyName == $prop->getName()) {
                if (is_array($value) != $prop->isMultiple()) {
                    if ($prop->isMultiple()) {
                        throw new ConstraintViolationException("The property definition is multivalued, but the value '$value' is not.");
                    }
                    if (is_array($value)) {
                        throw new ConstraintViolationException("The value $value is multivalued, but the property definition is not.");
                    }
                }
                if (PropertyType::UNDEFINED == $prop->getRequiredType()
                    || $type == $prop->getRequiredType()
                ) {
                    return true;
                }
                // try if we can convert. OPTIMIZE: would be nice to know without actually attempting to convert
                try {
                    $this->valueConverter->convertType($value, $prop->getRequiredType(), $type);

                    return true;
                } catch (ValueFormatException $e) {
                    // fall through and return false
                }
                if ($throw) {
                    throw new ConstraintViolationException("The property '$propertyName' with value '$value' can't be converted to an existing type.");
                }

                return false; // if there is an explicit match, it has to fit
            }
        }
        // now check if any of the wildcards matches
        foreach ($wildcards as $prop) {
            if (is_array($value) != $prop->isMultiple()) {
                continue;
            }
            if (PropertyType::UNDEFINED == $prop->getRequiredType()
                || $type == $prop->getRequiredType()
            ) {
                return true;
            }
            // try if we can convert. OPTIMIZE: would be nice to know without actually attempting to convert
            try {
                $this->valueConverter->convertType($value, $prop->getRequiredType(), $type);

                return true;
            } catch (ValueFormatException $e) {
                if ($throw) {
                    throw new ValueFormatException($propertyName.': '.$e->getMessage(), $e->getCode(), $e);
                }

                return false; // if there is an explicit match, it has to fit
            }
        }
        if ($throw) {
            $val = is_object($value) ? get_class($value) : (is_scalar($value) ? (string) $value : gettype($value));
            throw new ConstraintViolationException("Node type definition does not allow to set the property with name '$propertyName' and value '$val'");
        }

        return false;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function canAddChildNode($childNodeName, $nodeTypeName = null, $throw = false)
    {
        $childDefs = $this->getChildNodeDefinitions();
        if ($nodeTypeName) {
            try {
                $nodeType = $this->nodeTypeManager->getNodeType($nodeTypeName);
                if ($nodeType->isMixin() || $nodeType->isAbstract()) {
                    if ($throw) {
                        if ($nodeType->isMixin()) {
                            $errorMsg = "Can't add the child node '$childNodeName' for node type '$nodeTypeName' because the node type is mixin.";
                        } else {
                            $errorMsg = "Can't add the child node '$childNodeName for node type '$nodeTypeName' because the node type is abstract.";
                        }
                        throw new ConstraintViolationException($errorMsg);
                    }

                    return false;
                }
            } catch (NoSuchNodeTypeException $nsnte) {
                if ($throw) {
                    throw $nsnte;
                }

                return false;
            } catch (Exception $e) {
                if ($throw) {
                   $errorMsg = "Can't add the child node '$childNodeName' for node type '$nodeTypeName' because of an Exception: " . $e->getMessage();
                   throw new ConstraintViolationException($errorMsg, null, $e);
                }

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
        if ($throw) {
            $errorMsg = "Can't add the child node '$childNodeName' for node type '$nodeTypeName' because there is no definition for a child with that name.";
            throw new ConstraintViolationException($errorMsg);
        }

        return false;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function canRemoveNode($nodeName, $throw = false)
    {
        $childDefs = $this->getChildNodeDefinitions();
        foreach ($childDefs as $child) {
            if ($nodeName == $child->getName() &&
                ( $child->isMandatory() || $child->isProtected() )
            ) {
                if ($throw) {
                    if ($child->isMandatory()) {
                        $errorMsg = "Can't remove the mandatory childnode: " . $child->getName();
                    } else {
                        $errorMsg = "Can't remove the protected childnode: " . $child->getName();
                    }
                    throw new ConstraintViolationException($errorMsg);
                }

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
    public function canRemoveProperty($propertyName, $throw = false)
    {
        $propDefs = $this->getPropertyDefinitions();
        foreach ($propDefs as $prop) {
            if ($propertyName == $prop->getName() &&
                ( $prop->isMandatory() || $prop->isProtected() )
            ) {
                if ($throw) {
                    if ($prop->isMandatory()) {
                        $errorMsg = "Can't remove the mandatory property: " . $prop->getName();
                    } else {
                        $errorMsg = "Can't remove the protected property: " . $prop->getName();
                    }
                    throw new ConstraintViolationException($errorMsg);
                }

                return false;
            }
        }

        return true;
    }
}
