<?php
namespace Jackalope;

use \DOMElement;

/**
 * Static helper functions to do some commonly used dom and path operations
 *
 * @private
 */
class Helper
{
    /**
     * Returns a dom attribute casted to boolean.
     *
     * The attribute can contain the string 'false' which is interpreted as
     * false, everything else is true.
     *
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
}
