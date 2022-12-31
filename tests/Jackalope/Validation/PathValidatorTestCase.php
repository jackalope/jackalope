<?php

namespace Jackalope\Validation;

use Jackalope\TestCase;
use Jackalope\Validation\Exception\InvalidPathException;
use PHPCR\ValueFormatException;

abstract class PathValidatorTestCase extends TestCase
{
    /**
     * @return PathValidatorInterface
     */
    abstract protected function getValidator();

    abstract protected function getPathAnswers();

    abstract protected function getNameAnswers();

    public function provideValidatePath()
    {
        return [
            // absolute paths
            ['absolute_1', '/foo/bar', true],
            ['absolute_2', 'foo/bar', true],
            ['absolute_3', 'foo', true],

            // valid normal paths
            ['normal_1', '../foo/bar', false],
            ['normal_2', '.../foo/bar', false],
            ['normal_3', 'foo ', false],
            ['normal_4', 'foo/', false],
            ['normal_5', 'foo/bar[2]', false],
            ['normal_6', 'foo[1]/bar[2]', false],
            ['normal_7', '12345/6789', false],
            ['normal_8', 'foo:bar/bar:foo', false],
            ['normal_9', 'foo:!bar/bar:foo', false],
            ['normal_10', ' /foo', false],
            ['normal_11', '[]foo', false],
            ['normal_12', '    ', false],
            ['normal_13', '/', false],
            ['normal_14', '!foo:!bar/bar:foo', false],
        ];
    }

    /**
     * @dataProvider provideValidatePath
     */
    public function testValidatePath($key, $path, $absolute)
    {
        $pathAnswers = $this->getPathAnswers();

        if (!isset($pathAnswers[$key])) {
            throw new \InvalidArgumentException(sprintf(
                'Validator test class "%s" did not provide an answer for path "%s" with key "%s"',
                get_class($this),
                $path,
                $key
            ));
        }

        $isValid = $pathAnswers[$key];

        if (false === $isValid) {
            $this->expectException(ValueFormatException::class);
        }

        if ($absolute) {
            $this->getValidator()->validateAbsPath($path);
        } else {
            $this->getValidator()->validatePath($path);
        }

        $this->addToAssertionCount(1);
    }

    public function provideValidateName()
    {
        return [
            ['normal_1', 'this is invalid:foobar'],
            ['normal_2', '/this/is/a/path'], // path charaters
            ['normal_3', 'this:is valid'],
            ['normal_4', 'Thisisvalidtoo!'],
            ['normal_5', 'Thisisvalidtoo!:foobar'], // ! not allowed in namespace
            ['normal_6', 'this is something'],

            // strange characters in namespace
            ['namespace_1', $this->translateCharFromCode('\uD7FF').':foo'],
            ['namespace_2', $this->translateCharFromCode('\uFFFD').':foo'],
            ['namespace_3', $this->translateCharFromCode('\u10000').':foo'],
            ['namespace_4', $this->translateCharFromCode('\u10FFFF').':foo'],
            ['namespace_5', $this->translateCharFromCode('\u0001').':foo'],
            ['namespace_6', $this->translateCharFromCode('\u0002').':foo'],
            ['namespace_7', $this->translateCharFromCode('\u0003').':foo'],
            ['namespace_8', $this->translateCharFromCode('\u0008').':foo'],
            ['namespace_9', $this->translateCharFromCode('\uFFFF').':foo'],

            // strange characters in name
            ['localname_1', 'foo:'.$this->translateCharFromCode('\u0001')],
            ['localname_2', 'foo:'.$this->translateCharFromCode('\u0002')],
            ['localname_3', 'foo:'.$this->translateCharFromCode('\u0003')],
            ['localname_4', 'foo:'.$this->translateCharFromCode('\u0008')],
            ['localname_5', 'foo:'.$this->translateCharFromCode('\uFFFD')],
        ];
    }

    /**
     * @dataProvider provideValidateName
     */
    public function testValidateName($key, $name)
    {
        $nameAnswers = $this->getNameAnswers();

        if (!isset($nameAnswers[$key])) {
            throw new \InvalidArgumentException(sprintf(
                'Validator test class "%s" did not provide an answer for name "%s" with key "%s"',
                get_class($this),
                $name,
                $key
            ));
        }

        $isValid = $nameAnswers[$key];

        if (false === $isValid) {
            $this->expectException(ValueFormatException::class);
        }

        $this->getValidator()->validateName($name);

        $this->addToAssertionCount(1);
    }

    private function translateCharFromCode($char)
    {
        return json_decode('"'.$char.'"');
    }

    public function provideDestPath()
    {
        return [
            ['/path/to[0]', false],
            ['path/to/something', false],
            ['', false],
            ['/path/to/this', true],
        ];
    }

    /**
     * @dataProvider provideDestPath
     *
     * @throws \PHPUnit_Framework_Exception
     */
    public function testDestPath($path, $isValid)
    {
        if (false === $isValid) {
            $this->expectException(InvalidPathException::class);
        }

        $this->getValidator()->validateDestPath($path);

        $this->addToAssertionCount(1);
    }
}
