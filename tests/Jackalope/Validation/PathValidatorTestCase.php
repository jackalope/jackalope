<?php

namespace Jackalope\Validation;

use Jackalope\NodeType\NodeProcessor;
use Jackalope\TestCase;
use PHPCR\PropertyType;
use Jackalope\Validation\JackrabbitPathValidator;

abstract class PathValidatorTestCase extends TestCase
{
    abstract protected function getValidator();

    abstract protected function getPathAnswers();

    abstract protected function getNameAnswers();

    public function provideValidatePath()
    {
        return array(
            // absolute paths
            array('absolute_1', '/foo/bar', true),
            array('absolute_2', 'foo/bar', true),
            array('absolute_3', 'foo', true),

            // valid normal paths
            array('normal_1', '../foo/bar', false),
            array('normal_2', '.../foo/bar', false),
            array('normal_3', 'foo ', false),
            array('normal_4', 'foo/', false),
            array('normal_5', 'foo/bar[2]', false),
            array('normal_6', 'foo[1]/bar[2]', false),
            array('normal_7', '12345/6789', false),
            array('normal_8', 'foo:bar/bar:foo', false),
            array('normal_9', 'foo:!bar/bar:foo', false),
            array('normal_10', ' /foo', false),
            array('normal_11', '[]foo', false),
            array('normal_12', '    ', false),
            array('normal_13', '/', false),
            array('normal_14', '!foo:!bar/bar:foo', false),
        );
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
            $this->setExpectedException('PHPCR\ValueFormatException');
        }

        if ($absolute) {
            $this->getValidator()->validateAbsPath($path);
        } else {
            $this->getValidator()->validatePath($path);
        }
    }

    public function provideValidateName()
    {
        return array(
            array('normal_1', 'this is invalid:foobar'),
            array('normal_2', '/this/is/a/path'), // path charaters
            array('normal_3', 'this:is valid'),
            array('normal_4', 'Thisisvalidtoo!'),
            array('normal_5', 'Thisisvalidtoo!:foobar'), // ! not allowed in namespace
            array('normal_6', 'this is something'),

            // strange characters in namespace
            array('namespace_1', $this->translateCharFromCode('\uD7FF').':foo'),
            array('namespace_2', $this->translateCharFromCode('\uFFFD').':foo'),
            array('namespace_3', $this->translateCharFromCode('\u10000').':foo'),
            array('namespace_4', $this->translateCharFromCode('\u10FFFF').':foo'),
            array('namespace_5', $this->translateCharFromCode('\u0001').':foo'),
            array('namespace_6', $this->translateCharFromCode('\u0002').':foo'),
            array('namespace_7', $this->translateCharFromCode('\u0003').':foo'),
            array('namespace_8', $this->translateCharFromCode('\u0008').':foo'),
            array('namespace_9', $this->translateCharFromCode('\uFFFF').':foo'),

            // strange characters in name
            array('localname_1', 'foo:' . $this->translateCharFromCode('\u0001')),
            array('localname_2', 'foo:' . $this->translateCharFromCode('\u0002')),
            array('localname_3', 'foo:' . $this->translateCharFromCode('\u0003')),
            array('localname_4', 'foo:' . $this->translateCharFromCode('\u0008')),
            array('localname_5', 'foo:' . $this->translateCharFromCode('\uFFFD')),
        );
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
            $this->setExpectedException('PHPCR\ValueFormatException');
        }

        $this->getValidator()->validateName($name);
    }

    private function translateCharFromCode($char)
    {
        return json_decode('"' . $char . '"');
    }

    public function provideDestPath()
    {
        return array(
            array('/path/to[0]', false),
            array('path/to/something', false),
            array('', false),
            array('/path/to/this', true),
        );
    }

    /**
     * @dataProvider provideDestPath
     */
    public function testDestPath($path, $isValid)
    {
        if (false === $isValid) {
            $this->setExpectedException('Jackalope\Validation\Exception\InvalidPathException');
        }

        $this->getValidator()->validateDestPath($path);
    }
}

