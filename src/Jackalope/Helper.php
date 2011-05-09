<?php
namespace Jackalope;

use \DOMElement;

/**
 * static helper functions to do some commonly used dom and path operations
 *
 * Not part of the API, implementation specific
 */
class Helper
{

    /**
     * The expected date format to be used with {@link \DateTime}
     */
    const DATETIME_FORMAT = 'Y-m-d\TH:i:s.000P';

    /**
     * Returns an attribute casted to boolean
     * @param DOMElement node to fetch from
     * @param string attribute to fetch
     * @return bool the value converted to bool
     */
    public static function getBoolAttribute(DOMElement $node, $attribute)
    {
        if ('false' === $node->getAttribute($attribute)) {
            return false;
        }

        return true;
    }

    /**
     * Check if the path is an absolute path or not.
     *
     * @param   string  $path   The path to check
     * @return  bool    true if path is absolute otherwise false
     */
    public static function isAbsolutePath($path)
    {
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
     * @return  bool    true if valid otherwise false
     */
    public static function isValidPath($path)
    {
        return (strpos($path, '//') === false && preg_match('/^[\w{}\/#:^+~*\[\]\.-]*$/i', $path));
    }

    /**
     * Determine PropertyType from on variable type.
     *
     * This is most of the remainder of ValueFactory that is still needed.
     *
     * * if the given $value is a Node object, type will be REFERENCE, unless
     *    $weak is set to true which results in WEAKREFERENCE
     * * if the given $value is a DateTime object, the type will be DATE.
     *
     * @param mixed $value The variable we need to know the type of
     * @param boolean $weak When a Node is given as $value this can be given as true to create a WEAKREFERENCE.
     * @return One of the \PHPCR\PropertyType constants
     * @api
     */
    public static function determineType($value, $weak = false)
    {
        //determine type from variable type of value.
        //this is mainly needed to create a new property
        if (is_string($value)) {
            $type = \PHPCR\PropertyType::STRING;
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
        } else {
            throw new \PHPCR\ValueFormatException('Can not determine type of property with value "'.var_export($value, true).'"');
        }
        return $type;
    }

    /**
     * Attempt to convert $values into the proper format for $type.
     *
     * This is the other remaining part of ValueFactory functionality that is
     * still needed.
     *
     * Note that for converting to boolean, we follow the PHP convention of
     * treating any non-empty string as true, not just the word "true".
     *
     * @param mixed $values The value or value array to check and convert
     * @param int $type Target type to convert into. One of the type constants in \PHPCR\PropertyType
     * @return the value typecasted into the proper format (throws an exception if conversion is not possible)
     *
     * @throws \PHPCR\ValueFormatException is thrown if the specified value cannot be converted to the specified type.
     * @throws \PHPCR\RepositoryException if the specified Node is not referenceable, the current Session is no longer active, or another error occurs.
     * @throws IllegalArgumentException if the specified DateTime value cannot be expressed in the ISO 8601-based format defined in the JCR 2.0 specification and the implementation does not support dates incompatible with that format.
     */
    public static function convertType($values, $type)
    {
        $ret = null;
        $isArray = is_array($values);
        if (!$isArray) {
            $values = array($values);
        } else {
            $ret = array();
        }
        switch($type) {
            case \PHPCR\PropertyType::STRING:
                foreach ($values as $v) {
                    if (is_bool($v)) {
                        $ret[] = $v ? 'true' : 'false';
                    } elseif ($v instanceof \DateTime) {
                        $ret[] = $v->format(self::DATETIME_FORMAT);
                    } else {
                        settype($v, 'string');
                        $ret[] = $v;
                    }
                }
                break;
            case \PHPCR\PropertyType::LONG:
                $typename = 'integer';
                break;
            case \PHPCR\PropertyType::DECIMAL:
            case \PHPCR\PropertyType::DOUBLE:
                $typename = 'double';
                break;
            case \PHPCR\PropertyType::BOOLEAN:
                /*
                 * When converting String values to boolean, JCR uses
                 * java.lang.Boolean.valueOf(String) which evaluates to true only for the
                 * string "true" (case insensitive).
                 * PHP usually treats everything not null|0|false as true. The PHPCR API
                 * follows the JCR specification here in order to be consistent.
                 */
                foreach ($values as $v) {
                    $ret[] = $v === true || is_string($v) && strcasecmp('true', $v) == 0;
                }
                break;
            case \PHPCR\PropertyType::DATE:
                foreach ($values as $v) {
                    $datetime = false;
                    if ($v instanceof \DateTime) {
                        $datetime = $v;
                    } elseif (is_int($v)) {
                        $datetime = new \DateTime();
                        $datetime = $datetime->setTimestamp($v);
                    } elseif (is_string($v)) {
                        try {
                            $datetime = new \DateTime($v);
                        } catch (\Exception $e) {
                            $datetime = false;
                        }
                    }
                    if ($datetime === false) {
                        throw new \PHPCR\ValueFormatException('Can not convert "'.var_export($v, true).'" into a date');
                    }
                    $ret[] = $datetime;
                }
                break;
            case \PHPCR\PropertyType::REFERENCE:
            case \PHPCR\PropertyType::WEAKREFERENCE:
                foreach ($values as $v) {
                    if ($v instanceof \PHPCR\NodeInterface) {
                        $id = $v->getIdentifier();
                        //TODO: we should check the type if node is referencable, not rely on getting no identifier
                        if (empty($id)) {
                            throw new \PHPCR\ValueFormatException('Node ' . $v->getPath() . ' is not referencable');
                        }
                        $ret[] = $id;
                    } elseif (is_string($v) && ! empty($v)) {
                        //could check if string is valid uuid, but backend will do that
                        $ret[] = $v;
                    } else {
                        throw new \PHPCR\ValueFormatException("$v is not a unique id");
                    }
                }
                break;
            case \PHPCR\PropertyType::BINARY:
                foreach ($values as $v) {
                    // TODO handle file handles?
                    if (is_string($v)) {
                        $f = fopen('php://memory', 'rwb+');
                        fwrite($f, $v);
                        rewind($f);
                        $v = $f;
                    }

                    if (!is_resource($v)) {
                        throw new \PHPCR\ValueFormatException('Cannot convert value into a binary resource');
                    }

                    $ret[] = $v;
                }
            //FIXME: type PATH is missing. should automatically read property and node with getPath.
            default:
                //FIXME: handle other types somehow
                foreach ($values as $v) {
                    $ret[] = $v;
                }
                break;
            //TODO: more type checks or casts? name, path, uri, decimal. but the backend can handle the checks.
        }
        if (isset($typename)) {
            foreach ($values as $v) {
                if (! settype($v, $typename)) {
                    throw new \PHPCR\ValueFormatException;
                }
                $ret[] = $v;
            }
        }
        if (!$isArray) {
            $ret = $ret[0];
        }
        return $ret;
    }
}
