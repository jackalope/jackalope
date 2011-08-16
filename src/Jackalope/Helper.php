<?php
namespace Jackalope;

use \DOMElement;

/**
 * static helper functions to do some commonly used dom and path operations
 *
 * @private
 */
class Helper
{
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
}
