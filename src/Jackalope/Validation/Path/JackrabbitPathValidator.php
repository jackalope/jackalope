<?php

namespace Jackalope\Validation\Path;

use DOMElement;

/**
 * Applies the same path validation rules as the Apache Jackrabbit JCR implementation.
 *
 * Namespaces need to be valid XML elements according to the XML specification:
 *
 *    http://www.w3.org/TR/2008/REC-xml-20081126/#NT-Name
 *
 * Local names can be any characters other than ":", "[", "]", "*", "'" and """
 *
 * XML regexes translated thanks to this post from stack overflow:
 *
 *    http://stackoverflow.com/questions/2519845/how-to-check-if-string-is-a-valid-xml-element-name/15188815#15188815
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @private
 */
class JackrabbitPathValidator extends AbstractRegexValidator
{
    protected $PAT_NAME_PREFIX_START_CHAR;
    protected $PAT_NAME_PREFIX_CHAR;
    protected $PAT_NAME_PREFIX;
    protected $PAT_NAME_SIMPLE_CHAR_NEGATION;
    protected $PAT_NAME_SIMPLE_CHAR;
    protected $PAT_NAME_SIMPLE_CHAR_NO_SPACE;
    protected $PAT_LOCAL_NAME;
    protected $PAT_NAME;
    protected $PAT_PATH_ELEMENT;
    protected $NAME;
    protected $LOCAL_NAME;
    protected $PATH_WITHOUT_LAST_SLASH;
    protected $PATH;

    public function __construct()
    {
        $this->PAT_NAME_PREFIX_START_CHAR =
            '[' .
            ':A-Z_a-z\\xC0-\\xD6\\xD8-\\xF6\\xF8-\\x{2FF}\\x{370}-\\x{37D}\\x{37F}-\\x{1FFF}\\x{200C}-\\x{200D}\\x{2070}-' .
            '\\x{218F}\\x{2C00}-\\x{2FEF}\\x{3001}-\\x{D7FF}\\x{F900}-\\x{FDCF}\\x{FDF0}-\\x{FFFD}\\x{10000}-\\x{EFFFF}' .
            ']';

        $this->PAT_NAME_PREFIX_CHAR = '(' . $this->PAT_NAME_PREFIX_START_CHAR . '|[.\\-0-9\\xB7\\x{0300}-\\x{036F}\\x{203F}-\\x{2040}])';

        $this->PAT_NAME_PREFIX = $this->PAT_NAME_PREFIX_CHAR . '*';

        $this->PAT_NAME_SIMPLE_CHAR_NEGATION = '^/:\[\]\*\'"';

        $this->PAT_NAME_SIMPLE_CHAR = '[' . $this->PAT_NAME_SIMPLE_CHAR_NEGATION . ']';

        $this->PAT_NAME_SIMPLE_CHAR_NO_SPACE = '[' . $this->PAT_NAME_SIMPLE_CHAR_NEGATION . '\s' . ']';

        $this->PAT_LOCAL_NAME = $this->PAT_NAME_SIMPLE_CHAR_NO_SPACE . '(' . $this->PAT_NAME_SIMPLE_CHAR . '*' . $this->PAT_NAME_SIMPLE_CHAR . ')?';

        $this->PAT_NAME = '((' . $this->PAT_NAME_PREFIX . '):)?' . $this->PAT_LOCAL_NAME;

        $this->PAT_PATH_ELEMENT = $this->PAT_NAME . '(\[[1-9]\d*\])?';

        $this->NAME = '^' . $this->PAT_NAME . '$';

        $this->LOCAL_NAME = '^' . $this->PAT_LOCAL_NAME .'$';

        $this->PATH_WITHOUT_LAST_SLASH = '(\./|\.\./|/)?' . '(' . $this->PAT_PATH_ELEMENT . '/)*' . $this->PAT_PATH_ELEMENT;

        $this->PATH = '^' . $this->PATH_WITHOUT_LAST_SLASH . '/?$';
    }

    public function getNamePattern()
    {
        return $this->NAME;
    }

    public function getPathPattern()
    {
        return $this->PATH;
    }
}
