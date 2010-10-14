<?php
namespace jackalope;

use \PHPCR_ValueInterface, \PHPCR_PropertyType, \PHPCR_RepositoryException, \PHPCR_ItemNotFoundException, \PHPCR_PathNotFoundException, \PHPCR_ValueFormatException;

class Property extends Item implements \PHPCR_PropertyInterface {

    protected $value;
    protected $isMultiple = false;
    protected $type;
    protected $definition;

    /**
     * create a property, either from server data or locally
     * to indicate this has been created locally, make sure to pass true for the $new parameter
     *
     * @param mixed $data either array with fields
                        type (integer or string from PropertyType)
                        and value (data for creating value object)
                      or value object.
     * @param string $path the absolute path of this item
     * @param Session the session instance
     * @param ObjectManager the objectmanager instance - the caller has to take care of registering this item with the object manager
     * @param boolean $new optional: set to true to make this property aware its not yet existing on the server. defaults to false
     */
    public function __construct($data, $path, Session $session, ObjectManager $objectManager, $new = false) {
        parent::__construct(null, $path, $session, $objectManager, $new);

        if ($data instanceof PHPCR_ValueInterface ||
            is_array($data) && isset($data[0]) && $data[0] instanceof PHPCR_ValueInterface) {
            $this->value = $data;
        } else {
            if (! is_array($data)) throw new PHPCR_RepositoryException("Invalid data to create property. $data");
            $type = $data['type'];
            if (is_string($type)) {
                $type = PHPCR_PropertyType::valueFromName($type);
            }
            $this->type = $type;

            if (is_array($data['value'])) {
                $this->isMultiple = true;
                $this->value = array();
                foreach ($data['value'] as $value) {
                    array_push($this->value, Factory::get('Value', array(
                        $type,
                        $value
                    )));
                }
            } else {
                $this->value = Factory::get('Value', array($type, $data['value']));
            }
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
     * For Node objects as value:
     * Sets this REFERENCE OR WEAKREFERENCE property to refer to the specified
     * node. If this property is not of type REFERENCE or WEAKREFERENCE or the
     * specified node is not referenceable then a ValueFormatException is thrown.
     *
     * If value is an array:
     * If this property is not multi-valued then a ValueFormatException is
     * thrown immediately.
     *
     * Note: the Java API defines this method with multiple differing signatures.
     *
     * @param mixed $value The value to set
     * @return void
     * @throws PHPCR_ValueFormatException if the type or format of the specified value is incompatible with the type of this property.
     * @throws PHPCR_Version_VersionException if this property belongs to a node that is read-only due to a checked-in node and this implementation performs this validation immediately.
     * @throws PHPCR_Lock_LockException if a lock prevents the setting of the value and this implementation performs this validation immediately.
     * @throws PHPCR_ConstraintViolationException if the change would violate a node-type or other constraint and this implementation performs this validation immediately.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function setValue($value) {
        if (is_array($value) && ! $this->isMultiple)
            throw new PHPCR_ValueFormatException('Can not set a single value property with an array of values');
        if ($value instanceof PHPCR_NodeInterface) {
            if ($this->type == PHPCR_PropertyType::REFERENCE ||
                $this->type == PHPCR_PropertyType::WEAKREFERENCE) {
                //FIXME how to test if node is referenceable?
                //throw new PHPCR_ValueFormatException('reference property may only be set to a referenceable node');
                $this->value = Factory::get('Value', array($this->type, $value->getIdentifier())); //the value has to return the referenced node id string, so this is automatically fine
            } else {
               throw new PHPCR_ValueFormatException('A non-reference property can not have a node as value');
            }
        } elseif ($value instanceof PHPCR_ValueInterface) {
            if ($this->type == $value->getType()) {
                $this->value = $value;
            } else {
                throw new NotImplementedException('converting value seems like pain. do we have to?');
            }
        } elseif (is_null($value)) {
            $this->remove();
        } else {
            //TODO: some sanity check on types? do we care?
            $this->value = Factory::get('Value', array($this->type, $value));
        }
        $this->setModified(); //OPTIMIZE: should we detect setting to the same value and in that case not do anything?
    }

    /**
     * Returns the value of this property as a Value object.
     *
     * The object returned is a copy of the stored value and is immutable.
     *
     * @return PHPCR_ValueInterface the value
     * @throws PHPCR_ValueFormatException if the property is multi-valued.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function getValue() {
        $this->checkMultiple();
        return $this->value;
    }

    /**
     * Returns an array of all the values of this property. Used to access
     * multi-value properties. If the property is single-valued, this method
     * throws a ValueFormatException. The array returned is a copy of the
     * stored values, so changes to it are not reflected in internal storage.
     *
     * @return array of PHPCR_ValueInterface
     * @throws PHPCR_ValueFormatException if the property is single-valued.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function getValues() {
        $this->checkMultiple(false);
        return $this->value;
    }

    /**
     * Returns a String representation of the value of this property. A
     * shortcut for Property.getValue().getString(). See Value.
     *
     * @return string A string representation of the value of this property.
     * @throws PHPCR_ValueFormatException if conversion to a String is not possible or if the property is multi-valued.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function getString() {
        $this->checkMultiple();
        return $this->value->getString();
    }

    /**
     * Returns a Binary representation of the value of this property. A
     * shortcut for Property.getValue().getBinary(). See Value.
     *
     * @return PHPCR_BinaryInterface A Binary representation of the value of this property.
     * @throws PHPCR_ValueFormatException if the property is multi-valued.
     * @throws PHPCR_RepositoryException if another error occurs
     * @api
     */
    public function getBinary() {
        $this->checkMultiple();
        return $this->value->getBinary();
    }

    /**
     * Returns an integer representation of the value of this property. A shortcut
     * for Property.getValue().getLong(). See Value.
     *
     * @return integer An integer representation of the value of this property.
     * @throws PHPCR_ValueFormatException if conversion to a long is not possible or if the property is multi-valued.
     * @throws PHPCR_RepositoryException if another error occurs
     * @api
     */
    public function getLong() {
        $this->checkMultiple();
        return $this->value->getLong();
    }

    /**
     * Returns a double representation of the value of this property. A
     * shortcut for Property.getValue().getDouble(). See Value.
     *
     * @return float A float representation of the value of this property.
     * @throws PHPCR_ValueFormatException if conversion to a double is not possible or if the property is multi-valued.
     * @throws PHPCR_RepositoryException if another error occurs
     * @api
     */
    public function getDouble() {
        $this->checkMultiple();
        return $this->value->getDouble();
    }

    /**
     * Returns a BigDecimal representation of the value of this property. A
     * shortcut for Property.getValue().getDecimal(). See Value.
     *
     * @return float A float representation of the value of this property.
     * @throws PHPCR_ValueFormatException if conversion to a BigDecimal is not possible or if the property is multi-valued.
     * @throws PHPCR_RepositoryException if another error occurs
     * @api
     */
    public function getDecimal() {
        $this->checkMultiple();
        return $this->value->getDecimal();
    }

    /**
     * Returns a DateTime representation of the value of this property. A
     * shortcut for Property.getValue().getDate(). See Value.
     *
     * @return DateTime A date representation of the value of this property.
     * @throws PHPCR_ValueFormatException if conversion to a string is not possible or if the property is multi-valued.
     * @throws PHPCR_RepositoryException if another error occurs
     * @api
     */
    public function getDate() {
        $this->checkMultiple();
        return $this->value->getDate();
    }

    /**
     * Returns a boolean representation of the value of this property. A
     * shortcut for Property.getValue().getBoolean(). See Value.
     *
     * @return boolean A boolean representation of the value of this property.
     * @throws PHPCR_ValueFormatException if conversion to a boolean is not possible or if the property is multi-valued.
     * @throws PHPCR_RepositoryException if another error occurs
     * @api
     */
    public function getBoolean() {
        $this->checkMultiple();
        return $this->value->getBoolean();
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
     * @return PHPCR_NodeInterface the referenced Node
     * @throws PHPCR_ValueFormatException if this property cannot be converted to a referring type (REFERENCE, WEAKREFERENCE or PATH), if the property is multi-valued or if this property is a referring type but is currently part of the frozen state of a version in version storage.
     * @throws PHPCR_ItemNotFoundException If this property is of type PATH or WEAKREFERENCE and no target node accessible by the current Session exists in this workspace. Note that this applies even if the property is a PATH and a property exists at the specified location. To dereference to a target property (as opposed to a target node), the method Property.getProperty is used.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function getNode() {
        $this->checkMultiple();
        switch($this->type) {
            case PHPCR_PropertyType::PATH:
                return $this->objectManager->getNode($this->value->getString(), $this->parentPath);
            case PHPCR_PropertyType::REFERENCE:
                try {
                    return $this->objectManager->getNode($this->value->getString);
                } catch(PHPCR_ItemNotFoundException $e) {
                    throw new PHPCR_RepositoryException('Internal Error: Could not find a referenced node. This should be impossible.');
                }
            case PHPCR_PropertyType::WEAKREFERENCE:
                return $this->objectManager->getNode($this->value->getString);
            default:
                throw new PHPCR_ValueFormatException('Property is not a reference, weakreference or path');
        }
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
     * @return PHPCR_PropertyInterface the referenced property
     * @throws PHPCR_ValueFormatException if this property cannot be converted to a PATH, if the property is multi-valued or if this property is a referring type but is currently part of the frozen state of a version in version storage.
     * @throws PHPCR_ItemNotFoundException If no property accessible by the current Session exists in this workspace at the specified path. Note that this applies even if a node exists at the specified location. To dereference to a target node, the method Property.getNode is used.
     * @throws PHPCR_RepositoryException if another error occurs
     * @api
     */
    public function getProperty() {
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
     * @throws PHPCR_ValueFormatException if this property is multi-valued.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function getLength() {
        $this->checkMultiple();
        if (\PHPCR_PropertyType::BINARY === $this->type) {
            throw new NotImplementedException('Binaries not implemented');
        } else {
            return strlen($this->value->getString());
        }
    }

    /**
     * Returns an array holding the lengths of the values of this (multi-value)
     * property in bytes where each is individually calculated as described in
     * getLength().
     *
     * @return array an array of lengths (integers)
     * @throws PHPCR_ValueFormatException if this property is single-valued.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function getLengths() {
        $this->checkMultiple(false);
        $ret = array();
        foreach ($this->value as $value) {
            if (\PHPCR_PropertyType::BINARY === $this->type) {
                throw new NotImplementedException('Binaries not implemented');
            } else {
                array_push($ret, strlen($value->getString()));
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
     * @return PHPCR_NodeType_PropertyDefinitionInterface a PropertyDefinition object.
     * @throws PHPCR_RepositoryException if an error occurs.
     * @api
     */
    public function getDefinition() {
        if (empty($this->definition)) {

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
     * @return integer an int
     * @throws PHPCR_RepositoryException if an error occurs
     * @api
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Returns TRUE if this property is multi-valued and FALSE if this property
     * is single-valued.
     *
     * @return boolean TRUE if this property is multi-valued; FALSE otherwise.
     * @throws PHPCR_RepositoryException if an error occurs.
     * @api
     */
    public function isMultiple() {
        return $this->isMultiple;
    }

    /**
     * Throws an exception if the property is multivalued
     * @throws PHPCR_ValueFormatException
     */
    protected function checkMultiple($isMultiple = true) {
        if ($isMultiple === $this->isMultiple) {
            throw new \PHPCR_ValueFormatException();
        }
    }
}

