<?php

namespace jackalope;

class Helper {
    /**
     * Returns an attribute casted to boolean
     * @param DOMElement node to fetch from
     * @param string attribute to fetch
     * @return bool the value converted to bool
     */
    public static function getBoolAttribute($node, $attribute) {
        if ('false' === $node->getAttribute($attribute)) {
            return false;
        } else {
            return true;
        }
    }

    /**
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
     * @param   string  $path   THe path to validate
     * @return  bool    TRUE if valid otherwise FALSE
     */ 
    public static function isValidPath($path) {
        return (strpos($path, '//') === false && preg_match('/^[\w{}\/#:^+~*\[\]\.-]*$/i', $path));
    }

}
