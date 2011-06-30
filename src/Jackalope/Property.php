<?php

namespace Jackalope;

use ArrayIterator;
use PHPCR\PropertyType;

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
    /** flag to call stream_wrapper_register only once */
    protected static $binaryStreamWrapperRegistered = false;

    protected $value;
    /** length (only used for binary property */
    protected $length;
    protected $isMultiple = false;
    protected $type;
    protected $definition;

    /**
     * Create a property, either from server data or locally
     *
     * To indicate a property has newly been created locally, make sure to pass
     * true for the $new parameter. In that case, you should pass an empty array
     * for $data and use setValue afterwards to let the type magic be handled.
     * Then multivalue is determined on setValue
     *
     * For binary properties, the value is the length of the data(s), not the data itself.
     *
     * @param object $factory  an object factory implementing "get" as described in \Jackalope\Factory
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

        if (empty($data) && $new == true) {
            return;
        }

        $type = $data['type'];
        if (is_string($type)) {
            $type = PropertyType::valueFromName($type);
        } elseif (!is_numeric($type)) {
            throw new \PHPCR\RepositoryException("INTERNAL ERROR -- No valid type specified ($type)");
        } else {
            //sanity check. this will throw InvalidArgumentException if $type is not a valid type
            PropertyType::nameFromValue($type);
        }
        $this->type = $type;

        if ($type == PropertyType::BINARY) {
            if (is_array($data['value'])) {
                $this->isMultiple = true;
            }
            $this->length = $data['value'];
            $this->value = null;
            return;
        }

        if (is_array($data['value'])) {
            $this->isMultiple = true;
            $this->value = array();
            foreach ($data['value'] as $value) {
                $this->value[] = PropertyType::convertType($value, $type);
            }
        } elseif (null !== $data['value']) {
            $this->value = PropertyType::convertType($data['value'], $type);
        } else {
            throw new \PHPCR\RepositoryException('INTERNAL ERROR -- data[value] may not be null');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function setValue($value, $type = PropertyType::UNDEFINED)
    {
        $this->checkState(false);

        $this->_setValue($value, $type);

        // Need to check both value and type, as native php type string is used for a number of phpcr types
        if ($this->value !== $value || $this->type !== $type) {
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
        $this->checkState(false);

        if (!$this->isMultiple()) {
            throw new \PHPCR\ValueFormatException('You can not add values to non-multiple properties');
        }
        $this->value[] = PropertyType::convertType($value, $this->type);
        $this->setModified();
    }

    /**
     * Tell this item that it has been modified.
     * Used when deleting a node to tell the parent node about modification.
     */
    public function setModified()
    {
        parent::setModified();
        $parent = $this->getParent();
        if (!is_null($parent)) {
            $parent->setModified();
        }
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
        $this->checkState();

        if ($this->type == PropertyType::REFERENCE
            || $this->type == PropertyType::WEAKREFERENCE
        ) {
            return $this->getNode();
        } elseif ($this->type == PropertyType::BINARY) {
            return $this->getBinary();
        }
        return $this->value;
    }

    /**
     * Get the value of this property to store in the storage backend.
     *
     * Path and reference properties are not resolved to the node objects.
     * If this is a binary property, from the moment this method has been
     * called the stream will be read from the transport layer again.
     *
     * @private
     */
    public function getValueForStorage()
    {
        $this->checkState();

        $value = $this->value;
        if (PropertyType::BINARY == $this->type) {
            //from now on,
            $this->value = null;
        }
        return $value;
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
        $this->checkState();

        if ($this->type == PropertyType::BINARY && empty($this->value)) {
            return PropertyType::convertType($this->getBinary(), PropertyType::STRING);
        }
        if ($this->type != PropertyType::STRING) {
            return PropertyType::convertType($this->value, PropertyType::STRING);
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
        $this->checkState();

        if ($this->type != PropertyType::BINARY) {
            return PropertyType::convertType($this->value, PropertyType::BINARY);
        }
        if ($this->value != null) {
            // new or updated property
            $val = is_array($this->value) ? $this->value : array($this->value);
            foreach($val as $s) {
                $stream = fopen('php://memory', 'rwb+');
                $pos = ftell($s);
                stream_copy_to_stream($s, $stream);
                rewind($stream);
                fseek($s, $pos); //go back to previous position
                $ret[] = $stream;
            }
            return is_array($this->value) ? $ret : $ret[0];
        }
        // register a stream wrapper to lazily load binary property values
        if (!self::$binaryStreamWrapperRegistered) {
            stream_wrapper_register('jackalope', 'Jackalope\\BinaryStreamWrapper');
            self::$binaryStreamWrapperRegistered = true;
        }
        // return wrapped stream
        if ($this->isMultiple()) {
            $results = array();
            // identifies all streams loaded by one backend call
            $token = md5(uniqid(mt_rand(), true));
            // start with part = 1 since 0 will not be parsed properly by parse_url
            for ($i = 1; $i <= count($this->length); $i++) {
                $results[] = fopen('jackalope://' . $token. '@' . $this->session->getRegistryKey() . ':' . $i . $this->path , 'rwb+');
            }
            return $results;
        }
        // single property case
        return fopen('jackalope://' . $this->session->getRegistryKey() . $this->path , 'rwb+');
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
        $this->checkState();

        if ($this->type != PropertyType::LONG) {
            return PropertyType::convertType($this->value, PropertyType::LONG);
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
        $this->checkState();

        if ($this->type != PropertyType::DOUBLE) {
            return PropertyType::convertType($this->value, PropertyType::DOUBLE);
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
        $this->checkState();

        if ($this->type != PropertyType::DECIMAL) {
            return PropertyType::convertType($this->value, PropertyType::DECIMAL);
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
        $this->checkState();

        if ($this->type != PropertyType::DATE) {
            return PropertyType::convertType($this->value, PropertyType::DATE);
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
        $this->checkState();

        if ($this->type != PropertyType::BOOLEAN) {
            return PropertyType::convertType($this->value, PropertyType::BOOLEAN);
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
        $this->checkState();

        $values = $this->isMultiple() ? $this->value : array($this->value);

        $results = array();
        switch($this->type) {
            case PropertyType::REFERENCE:
                try {
                    foreach ($values as $value) {
                        $results[] = $this->objectManager->getNode($value);
                    }
                } catch(\PHPCR\ItemNotFoundException $e) {
                    throw new \PHPCR\RepositoryException('Internal Error: Could not find a referenced node. This should be impossible.');
                }
                break;
            case PropertyType::WEAKREFERENCE:
                foreach ($values as $value) {
                    $results[] = $this->objectManager->getNode($value);
                }
                break;
            case PropertyType::PATH:
            case PropertyType::STRING:
            case PropertyType::NAME:
                foreach ($values as $value) {
                    $results[] = $this->objectManager->getNode($value, $this->parentPath);
                }
                break;
            default:
                throw new \PHPCR\ValueFormatException('Property is not a REFERENCE, WEAKREFERENCE or PATH (or convertible to PATH)');
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
        $this->checkState();

        $values = $this->isMultiple() ? $this->value : array($this->value);

        $results = array();
        switch($this->type) {
            case PropertyType::PATH:
            case PropertyType::STRING:
            case PropertyType::NAME:
                foreach ($values as $value) {
                    $results[] = $this->objectManager->getPropertyByPath($this->objectManager->absolutePath($this->parentPath, $value));
                }
                break;
            default:
                throw new \PHPCR\ValueFormatException('Property is not a PATH (or convertible to PATH)');
        }

        return $this->isMultiple() ? $results : $results[0];

    }

    /**
     * Returns the length(s) of the value(s) of this property.
     *
     * For a BINARY property, getLength returns the number of bytes.
     * For other property types, getLength returns the same value that would be
     * returned by calling strlen() on the value when it has been converted to a
     * STRING according to standard JCR propety type conversion.
     *
     * Returns -1 if the implementation cannot determine the length.
     *
     * For multivalue properties, the same rules apply, but returns an array of lengths
     * with the same order as the values have in getValue
     *
     * @return mixed integer with the length, for multivalue property array of lengths
     *
     * @throws \PHPCR\ValueFormatException if this property is multi-valued.
     * @throws \PHPCR\RepositoryException if another error occurs.
     * @api
     */
    public function getLength()
    {
        $this->checkState();

        if (PropertyType::BINARY === $this->type) {
            return $this->length;
        }

        $vals = $this->isMultiple ? $this->value : array($this->value);
        $ret = array();

        foreach($vals as $value) {
            try {
                $ret[] = strlen(PropertyType::convertType($value, PropertyType::STRING));
            } catch (\Exception $e) {
                $ret[] = -1;
            }
        }

        return $this->isMultiple ? $ret : $ret[0];
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
        $this->checkState();

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
        $this->checkState();

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
        $this->checkState();

        return $this->isMultiple;
    }

    /**
     * Throws an exception if the property is multivalued
     * @throws \PHPCR\ValueFormatException
     */
    protected function checkMultiple($isMultiple = true)
    {
        $this->checkState();

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
    public function remove()
    {
        $this->checkState(false);

        $meth = new \ReflectionMethod('\Jackalope\Node', 'unsetProperty');
        $meth->setAccessible(true);
        $meth->invokeArgs($this->getParent(), array($this->name));

        parent::remove();
    }

    /**
     * Provide Traversable interface: redirect to getNodes with no filter
     *
     * @return Iterator over all child nodes
     */
    public function getIterator()
    {
        $this->checkState();

        $value = $this->getValue();
        if (!is_array($value)) {
            $value = array($value);
        }
        return new ArrayIterator($value);
    }

    /**
     * Reload the property after an unnotified backend change.
     */
    protected function reload()
    {
        // TODO: implement
    }

    /**
     * Internaly used to get the raw value of the property.
     *
     * DO NOT USE.
     *
     * @return mixed
     * @see Property::getValue
     * @private
     */
    public function _getRawValue()
    {
        return $this->value;
    }

    /**
     * Internaly used to set the value of the property without any notification
     * of changes nor state change.
     *
     * DO NOT USE.
     *
     * @param mixed $value
     * @param string $type 
     * @see Property::setValue
     * @private
     */
    public function _setValue($value, $type = PropertyType::UNDEFINED)
    {
        if (is_null($value)) {
            $this->remove();
        }
        if (! is_integer($type)) {
            throw new \InvalidArgumentException("The type has to be one of the numeric constants defined in PHPCR\PropertyType. $type");
        }
        if ($this->isNew()) {
            $this->isMultiple = is_array($value);
        }

        if (is_array($value) && !$this->isMultiple) {
            throw new \PHPCR\ValueFormatException('Can not set a single value property ('.$this->name.') with an array of values');
        }

        //TODO: check if changing type allowed.
        /*
         * if ($type !== null && ! canHaveType($type)) {
         *   throw new ConstraintViolationException("Can not set this property to type ".PropertyType::nameFromValue($type));
         * }
         */

        if (PropertyType::UNDEFINED === $type) {
            $type = PropertyType::determineType(is_array($value) ? reset($value) : $value);
        }

        $targettype = $this->type;
        if ($this->type !== $type) {
            /* TODO: find out with node type definition if the new type is allowed
              if (canHaveType($type)) {
                  */
                  $targettype = $type;
                  /*
              } else {
                  //convert to property type
                  $targettype = $this->type;
              }
            */
        }

        $value = PropertyType::convertType($value, $targettype);

        if (PropertyType::BINARY === $targettype) {
            $stat = fstat($value); //TODO: read file into local context? fstat not available on all streams
            $this->length = $stat['size'];
        }

        $this->type = $targettype;
        $this->value = $value;
    }
}
