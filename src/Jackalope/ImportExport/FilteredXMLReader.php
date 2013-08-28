<?php

namespace Jackalope\ImportExport;

use XMLReader;

/**
 * An XML reader that can do what we need for jackalope:
 *
 * * skip whitespace, empty significant whitespace and comments.
 * * move to next element regardless of the hierarchy
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @author David Buchmann <david@liip.ch>
 */
class FilteredXMLReader extends XMLReader
{
    public function read()
    {
        while (parent::read()) {
            if (self::WHITESPACE !== $this->nodeType
                && self::COMMENT !== $this->nodeType
                && !(self::SIGNIFICANT_WHITESPACE == $this->nodeType && '' == trim($this->value))

            ) {
                return true;
            }
        }

        return false;
    }

    public function moveToNextElement()
    {
        while (parent::read()) {
            if (self::ELEMENT == $this->nodeType) {
                return true;
            }
        }

        return false;
    }
}
