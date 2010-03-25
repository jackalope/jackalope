<?php

class jackalope_Helper {
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
}