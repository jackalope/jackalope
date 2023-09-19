<?php

namespace Jackalope\Validation\Path;

use Jackalope\Validation\Exception\InvalidPathException;
use Jackalope\Validation\PathValidatorInterface;

/**
 * Abstract class for regex based validators.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @private
 */
abstract class AbstractRegexValidator implements PathValidatorInterface
{
    /**
     * Return a regular expression for a valid path.
     */
    abstract protected function getPathPattern(): string;

    /**
     * Return a regular expression for a valid name.
     */
    abstract protected function getNamePattern(): string;

    public function validatePath($path): void
    {
        if (false === $this->validate($path, $this->getPathPattern())) {
            throw new InvalidPathException(sprintf('Path "%s" is not valid', $path));
        }
    }

    public function validateAbsPath($path): void
    {
        if (0 !== strpos($path, '/')) {
            throw new InvalidPathException(sprintf('Path "%s" is not absolute', $path));
        }

        $this->validatePath($path);
    }

    public function validateDestPath($path): void
    {
        $this->validateAbsPath($path);

        if (']' === substr($path, -1)) {
            throw new InvalidPathException(sprintf('Destination path "%s" must not end with an index', $path));
        }
    }

    public function validateName($path): void
    {
        if (false === $this->validate($path, $this->getNamePattern())) {
            throw new InvalidPathException(sprintf('Name "%s" is not valid', $path));
        }
    }

    /**
     * @throws InvalidPathException
     */
    private function validate(string $path, string $pattern): bool
    {
        $pattern = '{'.$pattern.'}u';
        $isMatch = 1 === preg_match($pattern, $path);

        if (false === $isMatch) {
            throw new InvalidPathException(sprintf('"%s" does not match regexp "%s"', $path, $pattern));
        }

        return $isMatch;
    }
}
