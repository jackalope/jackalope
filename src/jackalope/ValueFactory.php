<?php

/**
 * The ValueFactory object provides methods for the creation Value objects that can
 * then be used to set properties.
 */
class jackalope_ValueFactory implements PHPCR_ValueFactoryInterface {

    /**
     * Returns a PHPCR_Binary object with a value consisting of the content of
     * the specified resource handle.
     * The passed resource handle is closed before this method returns either normally
     * or because of an exception.
     *
     * @param resource $handle
     * @return PHPCR_BinaryInterface
     * @throws PHPCR_RepositoryException if an error occurs.
     * @api
     */
    public function createBinary($handle) {
        throw new jackalope_NotImplementedException('How to handle binaries?');
    }

    /**
     * Returns a Value object with the specified value. If $type is given,
     * conversion is attempted before creating the Value object.
     *
     * If no type is given, the value is stored as is, i.e. it's type is
     * preserved. Exceptions are:
     * * if the given $value is a Node object, it's Identifier is fetched for the
     *   Value object and the type of that object will be REFERENCE
     * * if the given $value is a Node object, it's Identifier is fetched for the
     *   Value object and the type of that object will be WEAKREFERENCE if $weak
     *   is set to TRUE
     * * if the given $Value is a DateTime object, the Value type will be DATE.
     *
     * Note: The Java API defines this with multiple differing signatures, you
     *       need to reproduce this behaviour in your implementation.
     *
     * @param mixed $value The value to use when creating the Value object
     * @param integer $type Type request for the Value object
     * @param boolean $weak When a Node is given as $value this can be given as TRUE to create a WEAKREFERENCE. Ignored if $type is not null
     * @return PHPCR_ValueInterface
     * @throws PHPCR_ValueFormatException is thrown if the specified value cannot be converted to the specified type.
     * @throws PHPCR_RepositoryException if the specified Node is not referenceable, the current Session is no longer active, or another error occurs.
     * @throws IllegalArgumentException if the specified DateTime value cannot be expressed in the ISO 8601-based format defined in the JCR 2.0 specification and the implementation does not support dates incompatible with that format.
     * @api
     */
    public function createValue($value, $type = NULL, $weak = FALSE) {
        if (is_null($type)) {
            //determine type from variable type of value.
            //this is mainly needed to create a new property
            if (is_string($value)) {
                $type = PHPCR_PropertyType::STRING;
            //TODO: binary!
            } elseif (is_int($value)) {
                $type = PHPCR_PropertyType::LONG;
            } elseif (is_float($value)) {
                $type = PHPCR_PropertyType::DOUBLE;
            //there is no date class in php, its usually strings (or timestamp numbers)
            //explicitly specify the type param for a date string
            } elseif (is_bool($value)) {
                $type = PHPCR_PropertyType::BOOLEAN;
            //name, path, reference, weakreference, uri are string, explicitly specify type if you need
            //decimal is not really meaningful (its double only), explicitly specify type if you need
            } elseif (is_object($value) && $value instanceof PHPCR_Node) {
                $type = ($weak) ? PHPCR_PropertyType::WEAKREFERENCE : PHPCR_PropertyType::REFERENCE;
                $value = $value->getIdentifier();
            } else {
                $type = PHPCR_PropertyType::UNDEFINED;
            }
        } else {
            switch($type) {
                case PHPCR_PropertyType::STRING:
                    $value = (string) $value;
                    break;
                //TODO: what about binary?
                case PHPCR_PropertyType::LONG:
                    $value = (integer) $value;
                    break;
                case PHPCR_PropertyType::DOUBLE:
                    $value = (double) $value;
                    break;
                case PHPCR_PropertyType::DATE:
                    if (is_int($value)) $value = date('c', $value); //convert to ISO 8601
                    break;
                case PHPCR_PropertyType::BOOLEAN:
                    $value = (boolean) $value;
                    break;
                case PHPCR_PropertyType::REFERENCE:
                case PHPCR_PropertyType::WEAKREFERENCE:
                    if ($value instanceof PHPCR_NodeInterface) {
                        $value = $value->getIdentifier();
                    } elseif (! is_string($value)) {
                        throw new PHPCR_ValueFormatException("$value is not a unique id");
                    }
                    //could check if uuid, but backend will do that
                    break;
                default:
                    break;
                //TODO: more type checks or casts? name, path, uri, decimal. but the backend can handle the checks.
            }
        }
        return jackalope_Factory::get('Value', array(
                        $type,
                        $value
                    ));
    }

}
