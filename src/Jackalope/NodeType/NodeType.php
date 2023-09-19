<?php

namespace Jackalope\NodeType;

use PHPCR\NodeType\ConstraintViolationException;
use PHPCR\NodeType\NodeTypeInterface;
use PHPCR\NodeType\NoSuchNodeTypeException;
use PHPCR\PropertyType;
use PHPCR\RepositoryException;
use PHPCR\ValueFormatException;

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
     */
    private array $declaredSupertypes;

    /**
     * Cache of the aggregated super node type names so they need to be
     * aggregated only once.
     */
    private array $superTypeNames;

    /**
     * Cache of the aggregated super NodeType instances so they need to be
     * instantiated only once.
     *
     * @var NodeTypeInterface[]
     */
    private array $superTypes;

    /**
     * Cache of the collected property definitions so they need to be
     * instantiated only once.
     */
    private array $propertyDefinitions;

    /**
     * Cache of the aggregated child node definitions from this type and all
     * its super type so they need to be gathered and instantiated only once.
     */
    private array $childNodeDefinitions;

    /**
     * @api
     */
    public function getSupertypes(): array
    {
        if (!isset($this->superTypes)) {
            $this->superTypes = [];
            foreach ($this->getDeclaredSupertypes() as $superType) {
                $this->superTypes[] = $superType;
                $this->superTypes = array_merge($this->superTypes, $superType->getSupertypes());
            }
        }

        return $this->superTypes;
    }

    /**
     * @api
     */
    public function getSupertypeNames(): array
    {
        if (!isset($this->superTypeNames)) {
            $this->superTypeNames = [];
            foreach ($this->getSupertypes() as $superType) {
                $this->superTypeNames[] = $superType->getName();
            }
        }

        return $this->superTypeNames;
    }

    /**
     * @api
     */
    public function getDeclaredSupertypes(): array
    {
        if (!isset($this->declaredSupertypes)) {
            $this->declaredSupertypes = [];
            foreach ($this->declaredSuperTypeNames as $declaredSuperTypeName) {
                $this->declaredSupertypes[] = $this->nodeTypeManager->getNodeType($declaredSuperTypeName);
            }
        }

        return $this->declaredSupertypes;
    }

    /**
     * @api
     */
    public function getSubtypes(): \Iterator
    {
        return new \ArrayIterator($this->nodeTypeManager->getSubtypes($this->name));
    }

    /**
     * @api
     */
    public function getDeclaredSubtypes(): \Iterator
    {
        return new \ArrayIterator($this->nodeTypeManager->getDeclaredSubtypes($this->name));
    }

    /**
     * @api
     */
    public function isNodeType($nodeTypeName): bool
    {
        return $this->getName() === $nodeTypeName || in_array($nodeTypeName, $this->getSupertypeNames(), true);
    }

    /**
     * @api
     */
    public function getPropertyDefinitions(): array
    {
        if (!isset($this->propertyDefinitions)) {
            $this->propertyDefinitions = $this->getDeclaredPropertyDefinitions();
            foreach ($this->getSupertypes() as $nodeType) {
                $this->propertyDefinitions = array_merge($this->propertyDefinitions, $nodeType->getDeclaredPropertyDefinitions());
            }
        }

        return $this->propertyDefinitions;
    }

    /**
     * @api
     */
    public function getChildNodeDefinitions(): array
    {
        if (!isset($this->childNodeDefinitions)) {
            $this->childNodeDefinitions = $this->getDeclaredChildNodeDefinitions();
            foreach ($this->getSupertypes() as $nodeType) {
                $this->childNodeDefinitions = array_merge($this->childNodeDefinitions, $nodeType->getDeclaredChildNodeDefinitions());
            }
        }

        return $this->childNodeDefinitions;
    }

    /**
     * @throws ValueFormatException
     * @throws ConstraintViolationException
     * @throws \InvalidArgumentException
     * @throws RepositoryException
     *
     * @api
     */
    public function canSetProperty($propertyName, $value, $throw = false): bool
    {
        $propDefs = $this->getPropertyDefinitions();
        try {
            $type = $this->valueConverter->determineType($value);
        } catch (ValueFormatException $e) {
            if ($throw) {
                throw new ValueFormatException(sprintf('Invalid value for property "%s": %s', $propertyName, $e->getMessage()), $e->getCode(), $e);
            }

            return false;
        }

        // check explicit matches first and keep wildcard definitions for later

        $wildcards = [];
        foreach ($propDefs as $prop) {
            if ('*' === $prop->getName()) {
                $wildcards[] = $prop;
            } elseif ($propertyName === $prop->getName()) {
                if (is_array($value) !== $prop->isMultiple()) {
                    if ($prop->isMultiple()) {
                        throw new ConstraintViolationException("The property definition is multivalued, but the value '$value' is not.");
                    }

                    if (is_array($value)) {
                        throw new ConstraintViolationException('The value '.$this->getValueAsString($value).' is multivalued, but the property definition for ['.$this->getName()."]:$propertyName is not.");
                    }
                }

                $requiredType = $prop->getRequiredType();
                if (PropertyType::UNDEFINED === $requiredType || $type === $requiredType) {
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
        /** @var $prop PropertyDefinition */
        foreach ($wildcards as $prop) {
            if (is_array($value) !== $prop->isMultiple()) {
                continue;
            }
            $requiredType = $prop->getRequiredType();
            if (PropertyType::UNDEFINED === $requiredType || $type === $requiredType) {
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
            $val = $this->getValueAsString($value);

            throw new ConstraintViolationException('Node type definition '.$this->getName()." does not allow to set the property with name '$propertyName' and value '$val'");
        }

        return false;
    }

    /**
     * @throws ConstraintViolationException
     *
     * @api
     */
    public function canAddChildNode($childNodeName, $nodeTypeName = null, $throw = false): bool
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
            } catch (\Exception $e) {
                if ($throw) {
                    $errorMsg = "Can't add the child node '$childNodeName' for node type '$nodeTypeName' because of an Exception: ".$e->getMessage();
                    throw new ConstraintViolationException($errorMsg, null, $e);
                }

                return false;
            }
        }
        foreach ($childDefs as $child) {
            if ('*' === $child->getName() || $childNodeName === $child->getName()) {
                if (null === $nodeTypeName) {
                    if (null !== $child->getDefaultPrimaryTypeName()) {
                        return true;
                    }
                } else {
                    \assert(isset($nodeType));
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
     * @throws ConstraintViolationException
     *
     * @api
     */
    public function canRemoveNode($nodeName, $throw = false): bool
    {
        $childDefs = $this->getChildNodeDefinitions();
        foreach ($childDefs as $child) {
            if ($nodeName === $child->getName()
                && ($child->isMandatory() || $child->isProtected())
            ) {
                if ($throw) {
                    if ($child->isMandatory()) {
                        $errorMsg = "Can't remove the mandatory childnode: ".$child->getName();
                    } else {
                        $errorMsg = "Can't remove the protected childnode: ".$child->getName();
                    }
                    throw new ConstraintViolationException($errorMsg);
                }

                return false;
            }
        }

        return true;
    }

    /**
     * @throws ConstraintViolationException
     *
     * @api
     */
    public function canRemoveProperty($propertyName, $throw = false): bool
    {
        $propDefs = $this->getPropertyDefinitions();
        foreach ($propDefs as $prop) {
            if ($propertyName === $prop->getName()
                && ($prop->isMandatory() || $prop->isProtected())
            ) {
                if ($throw) {
                    if ($prop->isMandatory()) {
                        $errorMsg = "Can't remove the mandatory property: ".$prop->getName();
                    } else {
                        $errorMsg = "Can't remove the protected property: ".$prop->getName();
                    }
                    throw new ConstraintViolationException($errorMsg);
                }

                return false;
            }
        }

        return true;
    }

    /**
     * Get a string representation of the passed value for error reporting.
     */
    private function getValueAsString($value): string
    {
        if (is_object($value)) {
            return get_class($value);
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            return 'array of length '.count($value);
        }

        return gettype($value);
    }
}
