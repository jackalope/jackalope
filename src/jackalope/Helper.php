<?php
namespace jackalope;

use \DOMElement;

/** Implementation Class:
 *  static helper functions to do some commonly used dom and path operations
 */
class Helper {
    /**
     * Returns an attribute casted to boolean
     * @param DOMElement node to fetch from
     * @param string attribute to fetch
     * @return bool the value converted to bool
     */
    public static function getBoolAttribute(DOMElement $node, $attribute) {
        if ('false' === $node->getAttribute($attribute)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Check if the path is an absolute path or not.
     *
     * @param   string  $path   The path to check
     * @return  bool    TRUE if path is absolute otherwise FALSE
     */
    public static function isAbsolutePath($path) {
        return $path && $path[0] == '/';
    }

    /**
     * Whether the path conforms to the JCR Specs (see paragraph 3.2)
     *
     * TODO: only minimal check performed atm, not full specs
     * TODO: what do we need this for in the frontend? i think the backend will
     *       check and scream if path is not well
     *
     * @param   string  $path   THe path to validate
     * @return  bool    TRUE if valid otherwise FALSE
     */
    public static function isValidPath($path) {
        return (strpos($path, '//') === false && preg_match('/^[\w{}\/#:^+~*\[\]\.-]*$/i', $path));
    }

    /**
     * Determine PropertyType from on variable type.
     *
     * This is half of ValueFactory that is still needed.
     *
     * * if the given $value is a Node object, type will be REFERENCE, unless
     *    $weak is set to true which results in WEAKREFERENCE
     * * if the given $value is a DateTime object, the type will be DATE.
     *
     * @param mixed $value The variable we need to know the type of
     * @param boolean $weak When a Node is given as $value this can be given as TRUE to create a WEAKREFERENCE.
     * @return One of the \PHPCR\PropertyType constants
     * @api
     */
    public static function determineType($value, $weak = false) {
        //determine type from variable type of value.
        //this is mainly needed to create a new property
        if (is_string($value)) {
            $type = \PHPCR\PropertyType::STRING;
        //TODO: binary!
        //TODO: datetime type
        } elseif (is_int($value)) {
            $type = \PHPCR\PropertyType::LONG;
        } elseif (is_float($value)) {
            $type = \PHPCR\PropertyType::DOUBLE;
        //there is no date class in php, its usually strings (or timestamp numbers)
        //explicitly specify the type param for a date string
        } elseif (is_bool($value)) {
            $type = \PHPCR\PropertyType::BOOLEAN;
        //name, path, reference, weakreference, uri are string, explicitly specify type if you need
        //decimal is not really meaningful (its double only), explicitly specify type if you need
        } elseif (is_object($value) && $value instanceof \PHPCR\NodeInterface) {
            $type = ($weak) ?
                    \PHPCR\PropertyType::WEAKREFERENCE :
                    \PHPCR\PropertyType::REFERENCE;
            $value = $value->getIdentifier();
        } else {
            $type = \PHPCR\PropertyType::UNDEFINED;
        }
    }
    /**
     * Attempt to convert $value into the proper format for $type.
     *
     * This is the other half of ValueFactory that is still needed.
     *
     * Note that for converting to boolean, we follow the PHP convention of
     * treating any non-empty string as true, not just the word "true".
     *
     * @param $value The value or value array to check and convert
     * @param $type One of the type constants in \PHPCR\PropertyType
     * @return the value typecasted into the proper format (throws an exception if conversion is not possible)
     *
     * @throws \PHPCR\ValueFormatException is thrown if the specified value cannot be converted to the specified type.
     * @throws \PHPCR\RepositoryException if the specified Node is not referenceable, the current Session is no longer active, or another error occurs.
     * @throws IllegalArgumentException if the specified DateTime value cannot be expressed in the ISO 8601-based format defined in the JCR 2.0 specification and the implementation does not support dates incompatible with that format.
     */
    public static function convertType($value, $type) {
        $array = is_array($value);
        if (! $array) {
            $value = array($value);
        }
        switch($type) {
            case \PHPCR\PropertyType::STRING:
                $typename = 'string';
                break;
            //TODO: what about binary?
            case \PHPCR\PropertyType::LONG:
                $typename = 'integer';
                break;
            case \PHPCR\PropertyType::DOUBLE:
                $typename = 'double';
                break;
            case \PHPCR\PropertyType::BOOLEAN:
                $typename = 'boolean';
                break;
            case \PHPCR\PropertyType::DATE:
                foreach($value as $v) {
                    if (is_int($v)) $ret[] = date('c', $value); //convert to ISO 8601
                }
                //FIXME: need datetime object
                break;
            case \PHPCR\PropertyType::REFERENCE:
            case \PHPCR\PropertyType::WEAKREFERENCE:
                foreach($value as $v) {
                    if ($v instanceof \PHPCR\NodeInterface) {
                        $ret[] = $v->getIdentifier();
                    } elseif (! is_string($v)) { //FIXME: check for valid uuid?
                        throw new \PHPCR\ValueFormatException("$v is not a unique id");
                    }
                    //else: could check if string is valid uuid, but backend will do that
                }
                break;
            case \PHPCR\PropertyType::BINARY:
                throw new NotImplementedException('Binaries');
            default:
                //FIXME: handle other types somehow
                foreach($value as $v) $ret[] = $v;
                break;
            //TODO: more type checks or casts? name, path, uri, decimal. but the backend can handle the checks.
        }
        if (isset($typename)) {
            foreach($value as $v) {
                if (! settype($v, $typename)) {
                    throw new \PHPCR\ValueFormatException;
                }
                $ret[] = $v;
            }
        }
        if (! $array) $ret = $ret[0];
        return $ret;
    }
}
