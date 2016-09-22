<?php

namespace Jackalope\Validation;

use Jackalope\Validation\Exception\InvalidPathException;

/**
 * Interface for path / name validator.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @private
 */
interface PathValidatorInterface
{
    /**
     * Assert that the given path is valid
     *
     * @param string $path
     *
     * @return void
     * @throws InvalidPathException
     */
    public function validatePath($path);

    /**
     * Assert that the given path is a valid absolute path
     *
     * @param string $absPath
     *
     * @return void
     * @throws InvalidPathException
     */
    public function validateAbsPath($absPath);

    /**
     * Assert that the given path is a valid destination path
     *
     * @param string $destPath
     *
     * @return void
     * @throws InvalidPathException
     */
    public function validateDestPath($destPath);

    /**
     * Assert that the given path is a valid absolute path
     *
     * @param string $name
     *
     * @return void
     * @throws InvalidPathException
     */
    public function validateName($name);
}
