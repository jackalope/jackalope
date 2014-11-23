<?php

namespace Jackalope\NodeType;

use Jackalope\NodeType\NodeProcessor;
use Jackalope\TestCase;
use PHPCR\PropertyType;
use Jackalope\Validation\JackrabbitPathValidator;

class JackrabbitPathValidatorTest extends TestCase
{
    public function setUp()
    {
        $this->validator = new JackrabbitPathValidator();
    }

    public function provideValidatePath()
    {
        return array(
            // valid absolute paths
            array('/foo/bar', true, true),

            // invalid absolute paths
            array('foo/bar', true, false),
            array('foo', true, false),

            // valid normal paths
            array('../foo/bar', false, true),
            array('.../foo/bar', false, true),
            array('foo ', false, true),
            array('foo/', false, true),
            array('foo/bar[2]', false, true),
            array('foo[1]/bar[2]', false, true),
            array('12345/6789', false, true),
            array('foo:bar/bar:foo', false, true),
            array('foo:!bar/bar:foo', false, true),

            // invalid normal paths
            array(' /foo', false, false),
            array('[]foo', false, false),
            array('    ', false, false),
            array('    ', false, false),
            array('/', false, false),
            array('!foo:!bar/bar:foo', false, false),
        );
    }

    /**
     * @dataProvider provideValidatePath
     */
    public function testValidatePath($path, $absolute, $isValid)
    {
        if (false === $isValid) {
            $this->setExpectedException('PHPCR\ValueFormatException');
        }

        if ($absolute) {
            $this->validator->validateAbsPath($path);
        } else {
            $this->validator->validatePath($path);
        }
    }

    public function provideValidateName()
    {
        return array(
            array('this is invalid:foobar', false),
            array('/this/is/a/path', false), // path charaters
            array('this:is valid', true),
            array('Thisisvalidtoo!', true),
            array('Thisisvalidtoo!:foobar', false), // ! not allowed in namespace

            // valid strange characters in namespace
            array($this->translateCharFromCode('\uD7FF').':foo', true),
            array($this->translateCharFromCode('\uFFFD').':foo', true),
            array($this->translateCharFromCode('\u10000').':foo', true),
            array($this->translateCharFromCode('\u10FFFF').':foo', true),

            // invalid strange characters in namespace
            array($this->translateCharFromCode('\u0001').':foo', false),
            array($this->translateCharFromCode('\u0002').':foo', false),
            array($this->translateCharFromCode('\u0003').':foo', false),
            array($this->translateCharFromCode('\u0008').':foo', false),
            array($this->translateCharFromCode('\uFFFF').':foo', false),

            // invalid strange characters in name
            array('foo:' . $this->translateCharFromCode('\u0001'), true),
            array('foo:' . $this->translateCharFromCode('\u0002'), true),
            array('foo:' . $this->translateCharFromCode('\u0003'), true),
            array('foo:' . $this->translateCharFromCode('\u0008'), true),
            array('foo:' . $this->translateCharFromCode('\uFFFF'), true),
        );
    }

    /**
     * @dataProvider provideValidateName
     */
    public function testValidateName($name, $isValid)
    {
        if (false === $isValid) {
            $this->setExpectedException('PHPCR\ValueFormatException');
        }

        $this->validator->validateName($name);
    }

    private function translateCharFromCode($char)
    {
        return json_decode('"'.$char.'"');
    }
}
