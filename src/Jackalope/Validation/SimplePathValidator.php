<?php

namespace Jackalope\Validation;

use DOMElement;

/**
 * Simple path validator
 *
 * - Namespace: -, _ and alpha-numeric.
 * - Localname: -, _, alpha-numeric, space. Cannot begin or end with space.
 * - Path: May begin win /, .., Localname or Namespace
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @private
 */
class SimplePathValidator extends AbstractRegexValidator
{
    private $NAMESPACE;
    private $LOCALNAME_CHAR_NO_SPACES;
    private $LOCALNAME;
    private $NAME;
    private $PATH;

    public function __construct()
    {
        $this->NAMESPACE = '[-a-zA-Z0-9_]+';
        $this->LOCALNAME_CHAR_NO_SPACES = '-a-zA-Z0-9_';
        $this->LOCALNAME = '([' . $this->LOCALNAME_CHAR_NO_SPACES . '\s]+)?([' . $this->LOCALNAME_CHAR_NO_SPACES .'])';
        $this->NAME = '((' . $this->NAMESPACE .'):)?' . $this->LOCALNAME;
        $this->PATH = '((/|\.\./)?' . $this->NAME . ')+';
    }

    protected function getPathPattern()
    {
        return '^' . $this->PATH . '$';
    }

    protected function getNamePattern()
    {
        return '^' . $this->NAME . '$';
    }
}
