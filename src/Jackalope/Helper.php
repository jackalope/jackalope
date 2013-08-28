<?php
namespace Jackalope;

use DOMElement;

use PHPCR\RepositoryException;

/**
 * Static helper functions to do some commonly used dom and path operations
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
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
     * @param DOMElement $node      to fetch from
     * @param string     $attribute name to fetch
     *
     * @return bool the value converted to bool
     */
    public static function getBoolAttribute(DOMElement $node, $attribute)
    {
        if (! $node->hasAttribute($attribute)) {
            throw new RepositoryException("Expected attribute $attribute not found on ".$node->getNodePath());
        }
        if ('false' === $node->getAttribute($attribute)) {
            return false;
        }

        return true;
    }
}
