<?php
namespace Jackalope;

/**
 * A Property object represents the smallest granularity of content storage.
 * It has a single parent node and no children. A property consists of a name
 * and a value, or in the case of multi-value properties, a set of values all
 * of the same type.
 *
 * @api
 */
class Property extends Item implements \IteratorAggregate, \PHPCR\PropertyInterface
{
    protected $value;
    protected $isMultiple = false;
    protected $type;
    protected $definition;

    /**
     * Create a property, either from server data or locally
     *
     * To indicate a property has newly been created locally, make sure to pass
     * true for the $new parameter.
     *
     * @param object $factory  an object factory implementing "get" as described in \jackalope\Factory
     * @param array $data array with fields
     *                    type (integer or string from PropertyType)
     *                    and value (data for creating value object - array for multivalue property)
     * @param string $path the absolute path of this item
     * @param Session the session instance
     * @param ObjectManager the objectmanager instance - the caller has to take care of registering this item with the object manager
     * @param boolean $new optional: set to true to make this property aware its not yet existing on the server. defaults to false
     */
    public function __construct($factory, array $data, $path, Session $session, ObjectManager $objectManager, $new = false)
    {
        parent::__construct($factory, $path, $session, $objectManager, $new);

        $type = $data['type'];
        if (is_string($type)) {
            $type = \PHPCR\PropertyType::valueFromName($type);
        } elseif (!is_numeric($type)) {
            throw new \PHPCR\RepositoryException("INTERNAL ERROR -- No valid type specified ($type)");
        } else {
            //sanity check. this will throw InvalidArgumentException if $type is not a valid type
            \PHPCR\PropertyType::nameFromValue($type);
        }
        $this->type = $type;

        if (is_array($data['value'])) {
            $this->isMultiple = true;
            $this->value = array();
            foreach ($data['value'] as $value) {
                $this->value[] = Helper::convertType($value, $type);
            }
        } elseif (null !== $data['value']) {
            $this->value = Helper::convertType($data['value'], $type);
        } else {
            throw new \PHPCR\RepositoryException('INTERNAL ERROR -- data[value] may not be null');
        }
    }

    /**
     * Sets the value of this property to value. If this property's property
     * type is not constrained by the node type of its parent node, then the
     * property type may be changed. If the property type is constrained, then a
     * best-effort conversion is attempted.
     *
     * This method is a session-write and therefore requires a <code>save</code>
     * to dispatch the change.
     *
     * If no type is given, the value is stored as is, i.e. its type is
     * preserved. Exceptions are:
     * * if the given $value is a Node object, its Identifier is fetched and
     *   the type of this property is set to REFERENCE
     * * if the given $value is a Node object, its Identifier is fetched and
     *   the type of this property is set to WEAKREFERENCE if $weak is set to
     *   true
     * * if the given $value is a DateTime object, the property type will be
     *   set to DATE.
     *
     * For Node objects as value:
     * Sets this REFERENCE OR WEAKREFERENCE property to refer to the specified
     * node. If this property is not of type REFERENCE or WEAKREFERENCE or the
     * specified node is not referenceable then a ValueFormatException is thrown.
     *
     * If value is an array:
     * If this property is not multi-valued then a ValueFormatException is
     * thrown immediately.
     *
     * PHPCR Note: The Java API defines this method with multiple differing signatures.
     * PHPCR Note: Because we removed the Value interface, this method replaces
     * ValueFactory::createValue.
     *
     * @param mixed $value The value to set. Array for multivalue properties
     * @param integer $type Type request for the property, optional. Must be a constant from PropertyType
     * @param boolean $weak When a Node is given as $value this can be given as true to create a WEAKREFERENCE, by default a REFERENCE is created
     * @return void
     * @throws \PHPCR\ValueFormatException if the type or format of the specified value is incompatible with the type of this property.
     * @throws \PHPCR\Version\VersionException if this property belongs to a node that is read-only due to a checked-in node and this implementation performs this validation immediately.
     * @throws \PHPCR\Lock\LockException if a lock prevents the setting of the value and this implementation performs this validation immediately.
     * @throws \PHPCR\ConstraintViolationException if the change would violate a node-type or other constraint and this implementation performs this validation immediately.
     * @throws \PHPCR\RepositoryException if another error occurs.
     * @throws \IllegalArgumentException if the specified DateTime value cannot be expressed in the ISO 8601-based format defined in the JCR 2.0 specification and the implementation does not support dates incompatible with that format.
     * @api
     */
    public function setValue($value, $type = null, $weak = false)
    {
        $previousValue = $this->value;
        if (is_array($value) && !$this->isMultiple) {
            throw new \PHPCR\ValueFormatException('Can not set a single value property ('.$this->name.') with an array of values');
        }

        // TODO $type is actually never used
        if (null === $type) {
            if (null !== $this->type) {
                $type = $this->type;
            } else {
                $type = Helper::determineType(is_array($value) ? reset($value) : $value, $weak);
            }
        }

        if ($value instanceof \PHPCR\NodeInterface) {
            if ($this->type == \PHPCR\PropertyType::REFERENCE ||
                $this->type == \PHPCR\PropertyType::WEAKREFERENCE) {
                //FIXME check for the mix:referenceable mixintype
                //throw new \PHPCR\ValueFormatException('reference property may only be set to a referenceable node');
                $this->value = $value->getIdentifier();
            } else {
               throw new \PHPCR\ValueFormatException('A non-reference property can not have a node as value');
            }
        } elseif (is_null($value)) {
            $this->remove();
        } else {
            $this->value = Helper::convertType($value, $this->type);
        }

        if ($this->value !== $previousValue) {
            $this->setModified();
        }
    }

    /**
     * Appends a value to a multi-value property
     *
     * @param mixed value
     * @throws \PHPCR\ValueFormatException if the property is not multi-value
     */
    public function addValue($value)
    {
        if (!$this->isMultiple()) {
            throw new \PHPCR\ValueFormatException('You can not add values to non-multiple properties');
        }
        $this->value[] = $value;
        $this->setModified();
    }

    /**
     * Tell this item that it has been modified.
     * Used when deleting a node to tell the parent node about modification.
     */
    public function setModified()
    {
        parent::setModified();
        $this->getParent()->setModified();
    }

    /**
     * Get the value in format default for the PropertyType of this property.
     *
     * PHPCR Note: This directly returns the raw data for this property
     *
     * References and Weakreferences are resolved to node instances, while path
     * is returned as string.
     *
     * @return mixed value of this property, or array in case of multi-value
     */
    public function getValue()
    {
        if ($this->type == \PHPCR\PropertyType::REFERENCE ||
            $this->type == \PHPCR\PropertyType::WEAKREFERENCE) {
            return $this->getNode();
        } elseif ($this->type == \PHPCR\PropertyType::BINARY) {
            return $this->getBinary();
        }
        return $this->value;
    }

    /**
     * Returns a String representation of the value of this property. A
     * shortcut for Property.getValue().getString(). See Value.
     *
     * @return string A string representation of the value of this property.
     * @throws \PHPCR\ValueFormatException if conversion to a String is not possible
     * @throws \PHPCR\RepositoryException if another error occurs.
     * @api
     */
    public function getString()
    {
        if ($this->type != \PHPCR\PropertyType::STRING) {
            return Helper::convertType($this->value, \PHPCR\PropertyType::STRING);
        }
        return $this->value;
    }

    /**
     * Returns a stream with the data of this property.
     *
     * @return resource a php binary stream
     * @throws \PHPCR\RepositoryException if another error occurs
     * @api
     */
    public function getBinary()
    {
        if ($this->type != \PHPCR\PropertyType::BINARY) {
            return Helper::convertType($this->value, \PHPCR\PropertyType::BINARY);
        }
        return $this->value; //FIXME get the stream from transport each time!
    }

    /**
     * Returns an integer representation of the value of this property. A shortcut
     * for Property.getValue().getLong(). See Value.
     *
     * @return integer An integer representation of the value of this property.
     * @throws \PHPCR\ValueFormatException if conversion to a long is not possible
     * @throws \PHPCR\RepositoryException if another error occurs
     * @api
     */
    public function getLong()
    {
        if ($this->type != \PHPCR\PropertyType::LONG) {
            return Helper::convertType($this->value, \PHPCR\PropertyType::LONG);
        }
        return $this->value;
    }

    /**
     * Returns a double representation of the value of this property. A
     * shortcut for Property.getValue().getDouble(). See Value.
     *
     * @return float A float representation of the value of this property.
     * @throws \PHPCR\ValueFormatException if conversion to a double is not possible
     * @throws \PHPCR\RepositoryException if another error occurs
     * @api
     */
    public function getDouble()
    {
        if ($this->type != \PHPCR\PropertyType::DOUBLE) {
            return Helper::convertType($this->value, \PHPCR\PropertyType::DOUBLE);
        }
        return $this->value;
    }

    /**
     * Returns a BigDecimal representation of the value of this property. A
     * shortcut for Property.getValue().getDecimal(). See Value.
     *
     * @return float A float representation of the value of this property.
     * @throws \PHPCR\ValueFormatException if conversion to a BigDecimal is not possible or if the property is multi-valued.
     * @throws \PHPCR\RepositoryException if another error occurs
     * @api
     */
    public function getDecimal()
    {
        if ($this->type != \PHPCR\PropertyType::DECIMAL) {
            return Helper::convertType($this->value, \PHPCR\PropertyType::DECIMAL);
        }
        return $this->value;
    }

    /**
     * Returns a DateTime representation of the value of this property. A
     * shortcut for Property.getValue().getDate(). See Value.
     *
     * @return DateTime A date representation of the value of this property.
     * @throws \PHPCR\ValueFormatException if conversion to a string is not possible or if the property is multi-valued.
     * @throws \PHPCR\RepositoryException if another error occurs
     * @api
     */
    public function getDate()
    {
        if ($this->type != \PHPCR\PropertyType::DATE) {
            return Helper::convertType($this->value, \PHPCR\PropertyType::DATE);
        }
        return $this->value;
    }

    /**
     * Returns a boolean representation of the value of this property. A
     * shortcut for Property.getValue().getBoolean(). See Value.
     *
     * @return boolean A boolean representation of the value of this property.
     * @throws \PHPCR\ValueFormatException if conversion to a boolean is not possible or if the property is multi-valued.
     * @throws \PHPCR\RepositoryException if another error occurs
     * @api
     */
    public function getBoolean()
    {
        if ($this->type != \PHPCR\PropertyType::BOOLEAN) {
            return Helper::convertType($this->value, \PHPCR\PropertyType::BOOLEAN);
        }
        return $this->value;
    }

    /**
     * If this property is of type REFERENCE, WEAKREFERENCE or PATH (or
     * convertible to one of these types) this method returns the Node to
     * which this property refers.
     * If this property is of type PATH and it contains a relative path, it is
     * interpreted relative to the parent node of this property. For example "."
     * refers to the parent node itself, ".." to the parent of the parent node
     * and "foo" to a sibling node of this property.
     *
     * @return \PHPCR\NodeInterface the referenced Node
     * @throws \PHPCR\ValueFormatException if this property cannot be converted to a referring type (REFERENCE, WEAKREFERENCE or PATH), if the property is multi-valued or if this property is a referring type but is currently part of the frozen state of a version in version storage.
     * @throws \PHPCR\ItemNotFoundException If this property is of type PATH or WEAKREFERENCE and no target node accessible by the current Session exists in this workspace. Note that this applies even if the property is a PATH and a property exists at the specified location. To dereference to a target property (as opposed to a target node), the method Property.getProperty is used.
     * @throws \PHPCR\RepositoryException if another error occurs.
     * @api
     */
    public function getNode()
    {
        $values = $this->isMultiple() ? $this->value : array($this->value);

        $results = array();
        switch($this->type) {
            case \PHPCR\PropertyType::PATH:
                foreach ($values as $value) {
                    $results[] = $this->objectManager->getNode($value, $this->parentPath);
                }
                break;
            case \PHPCR\PropertyType::REFERENCE:
                try {
                    foreach ($values as $value) {
                        $results[] = $this->objectManager->getNode($value);
                    }
                } catch(\PHPCR\ItemNotFoundException $e) {
                    throw new \PHPCR\RepositoryException('Internal Error: Could not find a referenced node. This should be impossible.');
                }
                break;
            case \PHPCR\PropertyType::WEAKREFERENCE:
                foreach ($values as $value) {
                    $results[] = $this->objectManager->getNode($value);
                }
                break;
            default:
                throw new \PHPCR\ValueFormatException('Property is not a reference, weakreference or path');
        }

        return $this->isMultiple() ? $results : $results[0];
    }

    /**
     * If this property is of type PATH (or convertible to this type) this
     * method returns the Property to which this property refers.
     * If this property contains a relative path, it is interpreted relative
     * to the parent node of this property. Therefore, when resolving such a
     * relative path, the segment "." refers to the parent node itself, ".." to
     * the parent of the parent node and "foo" to a sibling property of this
     * property or this property itself.
     *
     * For example, if this property is located at /a/b/c and it has a value of
     * "../d" then this method will return the property at /a/d if such exists.
     *
     * @return \PHPCR\PropertyInterface the referenced property
     * @throws \PHPCR\ValueFormatException if this property cannot be converted to a PATH, if the property is multi-valued or if this property is a referring type but is currently part of the frozen state of a version in version storage.
     * @throws \PHPCR\ItemNotFoundException If no property accessible by the current Session exists in this workspace at the specified path. Note that this applies even if a node exists at the specified location. To dereference to a target node, the method Property.getNode is used.
     * @throws \PHPCR\RepositoryException if another error occurs
     * @api
     */
    public function getProperty()
    {
        throw new NotImplementedException();
    }

    /**
     * Returns the length of the value of this property.
     *
     * For a BINARY property, getLength returns the number of bytes.
     * For other property types, getLength returns the same value that would be
     * returned by calling strlen() on the value when it has been converted to a
     * STRING according to standard JCR propety type conversion.
     *
     * Returns -1 if the implementation cannot determine the length.
     *
     * @return integer an integer.
     * @throws \PHPCR\ValueFormatException if this property is multi-valued.
     * @throws \PHPCR\RepositoryException if another error occurs.
     * @api
     */
    public function getLength()
    {
        $this->checkMultiple();
        if (\PHPCR\PropertyType::BINARY === $this->type) {
            throw new NotImplementedException('Binaries not implemented');
        }

        try {
            return strlen(Helper::convertType($this->value, \PHPCR\PropertyType::STRING));
        } catch (Exception $e) {
            return -1;
        }
    }

    /**
     * Returns an array holding the lengths of the values of this (multi-value)
     * property in bytes where each is individually calculated as described in
     * getLength().
     *
     * @return array an array of lengths (integers)
     * @throws \PHPCR\ValueFormatException if this property is single-valued.
     * @throws \PHPCR\RepositoryException if another error occurs.
     * @api
     */
    public function getLengths()
    {
        $this->checkMultiple(false);
        $ret = array();
        foreach ($this->value as $value) {
            if (\PHPCR\PropertyType::BINARY === $this->type) {
                throw new NotImplementedException('Binaries not implemented');
            }
            try {
                $ret[] = strlen(Helper::convertType($value, \PHPCR\PropertyType::STRING));
            } catch(Exception $e) {
                $ret[] = -1;
            }
        }
        return $ret;
    }

    /**
     * Returns the property definition that applies to this property. In some
     * cases there may appear to be more than one definition that could apply
     * to this node. However, it is assumed that upon creation or change of
     * this property, a single particular definition is chosen by the
     * implementation. It is that definition that this method returns. How this
     * governing definition is selected upon property creation or change from
     * among others which may have been applicable is an implementation issue
     * and is not covered by this specification.
     *
     * @return \PHPCR\NodeType\PropertyDefinitionInterface a PropertyDefinition object.
     * @throws \PHPCR\RepositoryException if an error occurs.
     * @api
     */
    public function getDefinition()
    {
        if (empty($this->definition)) {
            //FIXME: acquire definition
        }
        return $this->definition;
    }

    /**
     * Returns the type of this Property. One of:
     * * PropertyType.STRING
     * * PropertyType.BINARY
     * * PropertyType.DATE
     * * PropertyType.DOUBLE
     * * PropertyType.LONG
     * * PropertyType.BOOLEAN
     * * PropertyType.NAME
     * * PropertyType.PATH
     * * PropertyType.REFERENCE
     * * PropertyType.WEAKREFERENCE
     * * PropertyType.URI
     *
     * The type returned is that which was set at property creation. Note that
     * for some property p, the type returned by p.getType() will differ from
     * the type returned by p.getDefinition.getRequiredType() only in the case
     * where the latter returns UNDEFINED. The type of a property instance is
     * never UNDEFINED (it must always have some actual type).
     *
     * @return integer type id of this property
     * @throws \PHPCR\RepositoryException if an error occurs
     * @api
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns true if this property is multi-valued and false if this property
     * is single-valued.
     *
     * @return boolean true if this property is multi-valued; false otherwise.
     * @throws \PHPCR\RepositoryException if an error occurs.
     * @api
     */
    public function isMultiple()
    {
        return $this->isMultiple;
    }

    /**
     * Throws an exception if the property is multivalued
     * @throws \PHPCR\ValueFormatException
     */
    protected function checkMultiple($isMultiple = true)
    {
        if ($isMultiple === $this->isMultiple) {
            throw new \PHPCR\ValueFormatException();
        }
    }

    /**
     * Also unsets internal reference in parent node
     *
     * {@inheritDoc}
     *
     * @return void
     * @uses Node::unsetProperty()
     * @api
     **/
    public function remove() {
        parent::remove();

        $meth = new \ReflectionMethod('\Jackalope\Node', 'unsetProperty');
        $meth->setAccessible(true);
        $meth->invokeArgs($this->getParent(), array($this->name));
    }

    /**
     * Provide Traversable interface: redirect to getNodes with no filter
     *
     * @return Iterator over all child nodes
     */
    public function getIterator()
    {
        $value = $this->getValue();
        if (!is_array($value)) {
            $value = array($value);
        }
        return new \ArrayIterator($value);
    }

}
