<?php
namespace jackalope;
use \PHPCR_PropertyType;

/**
 * A generic holder for the value of a property. A Value object can be used
 * without knowing the actual property type (STRING, DOUBLE, BINARY etc.).
 *
 * Any implementation of this interface must adhere to the following behavior:
 *
 * Two Value instances, v1 and v2, are considered equal if and only if:
 * * v1.getType() == v2.getType(), and,
 * * v1.getString().equals(v2.getString())
 *
 * Actually comparing two Value instances by converting them to string form may not
 * be practical in some cases (for example, if the values are very large binaries).
 * Consequently, the above is intended as a normative definition of Value equality
 * but not as a procedural test of equality. It is assumed that implementations
 * will have efficient means of determining equality that conform with the above
 * definition. An implementation is only required to support equality comparisons on
 * Value instances that were acquired from the same Session and whose contents have
 * not been read. The equality comparison must not change the state of the Value
 * instances even though the getString() method in the above definition implies a
 * state change.
 *
 * The deprecated getStream() method and it's related exceptions and rules have been
 * omitted in this PHP port of the API.
 */
class Value implements \PHPCR_ValueInterface {
    /** system type id */
    protected $type;
    protected $data;

    /**
     * @param mixed Type of the Value given: either id or name as in PHPCR_PropertyType
     * @param mixed Data that the value should contain
     */
    public function __construct($type, $data) {
        if (is_string($type)) {
            $type = PHPCR_PropertyType::valueFromName($type);
        }
        if (PHPCR_PropertyType::BINARY === $type) {
            throw new NotImplementedException('Binaries not implemented');
        }
        $this->type = $type;
        $this->data = $data;
    }

    /**
     * Returns a string representation of this value.
     * This is also used for node references because the uuid is used for that.
     *
     * @return string A string representation of the value of this property.
     * @throws PHPCR_ValueFormatException if conversion to a String is not possible.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function getString() {
        return $this->convertType('string');
    }

    /**
     * Returns a Binary representation of this value. The Binary object in turn provides
     * methods to access the binary data itself. Uses the standard conversion to binary
     * (see JCR specification).
     *
     * @return PHPCR_BinaryInterface A Binary representation of this value.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function getBinary() {
        throw new NotImplementedException('Binaries not implemented');
    }

    /**
     * Returns a long representation of this value.
     *
     * @return integer A long representation of the value of this property.
     * @throws PHPCR_ValueFormatException if conversion to a long is not possible.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function getLong() {
        return $this->convertType('int');
    }

    /**
     * Returns a double representation of this value (a BigDecimal in Java).
     *
     * @return float A double representation of the value of this property.
     * @throws PHPCR_ValueFormatException if conversion is not possible.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function getDecimal() {
        return $this->convertType('float');
    }

    /**
     * Returns a double representation of this value.
     *
     * @return float A double representation of the value of this property.
     * @throws PHPCR_ValueFormatException if conversion to a double is not possible.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function getDouble() {
        return $this->convertType('float');
    }

    /**
     * Returns a DateTime representation of this value.
     *
     * The object returned is a copy of the stored value, so changes to it are
     * not reflected in internal storage.
     *
     * @return DateTime A DateTime representation of the value of this property.
     * @throws PHPCR_ValueFormatException if conversion to a DateTime is not possible.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function getDate() {
        throw new NotImplementedException();
    }

    /**
     * Returns a boolean representation of this value.
     * According to jcr 2.0, strings are only true if they equal "true" (case insensitive)
     *
     * @return boolean A boolean representation of the value of this property.
     * @throws PHPCR_ValueFormatException if conversion to a boolean is not possible.
     * @throws PHPCR_RepositoryException if another error occurs.
     * @api
     */
    public function getBoolean() {
        if (is_string($this->data)) {
            return strcasecmp('true', $this->data) == 0;
        }
        return $this->convertType('bool');
    }

    /**
     * Returns the type of this Value. One of:
     * * PropertyType.STRING
     * * PropertyType.DATE
     * * PropertyType.BINARY
     * * PropertyType.DOUBLE
     * * PropertyType.DECIMAL
     * * PropertyType.LONG
     * * PropertyType.BOOLEAN
     * * PropertyType.NAME
     * * PropertyType.PATH
     * * PropertyType.REFERENCE
     * * PropertyType.WEAKREFERENCE
     * * PropertyType.URI
     *
     * The type returned is that which was set at property creation.
     *
     * @return integer The type of the value
     * @api
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param string $type The target type you want to retrieve
     * @return mixed The converted value
     * @throws PHPCR_ValueFormatException if conversion is not possible.
     */
    protected function convertType($type) {
        $ret = $this->data;
        if (settype($ret, $type)) {
            return $ret;
        } else {
            throw new \PHPCR_ValueFormatException;
        }
    }
}
