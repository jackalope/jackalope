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
}
