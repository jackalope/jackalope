<?php

namespace Jackalope\Validation;

use DOMElement;

/**
 * Abstract class for regex based validators
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @private
 */
abstract class AbstractRegexValidator implements PathValidatorInterface
{
    abstract protected function getPathPattern();

    abstract protected function getNamePattern();

    /**
     * {@inheritDoc}
     */
    public function validatePath($path)
    {
        if (false === $this->validate($path, $this->getPathPattern())) {
            throw new InvalidPathException(sprintf('Path "%s" is not valid', $path));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validateAbsPath($path)
    {
        if (substr($path, 0, 1) !== '/') {
            throw new InvalidPathException(sprintf('Path "%s" is not absolute', $path));
        }

        return $this->validatePath($path);
    }

    /**
     * {@inheritDoc}
     */
    public function validateName($path)
    {
        if (false === $this->validate($path, $this->getNamePattern())) {
            throw new InvalidPathException(sprintf('Name "%s" is not valid', $path));
        }
    }

    private function validate($path, $pattern)
    {
        $pattern = '{' . $pattern . '}u';
        $isMatch = 1 === preg_match($pattern, $path);

        if (false === $isMatch) {
            throw new InvalidPathException(sprintf(
                '"%s" does not match regexp "%s"', $path, $pattern
            ));
        }

        return $isMatch;
    }
}

