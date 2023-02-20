<?php

namespace Jackalope\Validation\Path;

/**
 * Simple path validator.
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
    private string $NAME;
    private string $PATH;

    public function __construct()
    {
        $NAMESPACE = '[-a-zA-Z0-9_]+';
        $LOCALNAME_CHAR_NO_SPACES = '-a-zA-Z0-9_';
        $LOCALNAME = '(['.$LOCALNAME_CHAR_NO_SPACES.'\s]+)?(['.$LOCALNAME_CHAR_NO_SPACES.'])';
        $this->NAME = '(('.$NAMESPACE.'):)?'.$LOCALNAME;
        $this->PATH = '((/|\.\./)?'.$this->NAME.')+';
    }

    protected function getPathPattern(): string
    {
        return '^'.$this->PATH.'$';
    }

    protected function getNamePattern(): string
    {
        return '^'.$this->NAME.'$';
    }
}
