<?php

namespace Jackalope;

use PHPCR\RepositoryException;

/**
 * Static helper functions to do some commonly used dom and path operations.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @private
 */
final class Helper
{
    /**
     * Returns a dom attribute casted to boolean.
     *
     * The attribute can contain the string 'false' which is interpreted as
     * false, everything else is true.
     *
     * @param \DOMElement $node      to fetch from
     * @param string      $attribute name to fetch
     *
     * @return bool the value converted to bool
     *
     * @throws RepositoryException
     */
    public static function getBoolAttribute(\DOMElement $node, string $attribute)
    {
        if (!$node->hasAttribute($attribute)) {
            throw new RepositoryException("Expected attribute $attribute not found on ".$node->getNodePath());
        }

        return 'false' !== $node->getAttribute($attribute);
    }
}
