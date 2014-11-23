<?php

namespace Jackalope\NodeType;

use Jackalope\NodeType\NodeProcessor;
use Jackalope\TestCase;
use PHPCR\PropertyType;
use Jackalope\Validation\SimplePathValidator;

class SimplePathValidatorTest extends TestCase
{
    public function setUp()
    {
        $this->validator = new SimplePathValidator();
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
            array('.../foo/bar', false, false),
            array('foo ', false, false),
            array('foo/', false, false),

            // same name siblings not supported by this validator
            array('foo/bar[2]', false, false),
            array('foo[1]/bar[2]', false, false),

            array('12345/6789', false, true),
            array('foo:bar/bar:foo', false, true),

            // invalid normal paths
            array('foo:!bar/bar:foo', false, false),
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
            array('this is valid', true),

            array('this is invalid:foobar', false),
            array('/this/is/a/path', false), // path charaters
            array('this:is valid', true),
            array('Thisisinvalidtoo!', false),
            array('Thisisinvalidtoo!:foobar', false), // ! not allowed in namespace

            // valid strange characters in namespace
            array($this->translateCharFromCode('\uD7FF').':foo', false),
            array($this->translateCharFromCode('\uFFFD').':foo', false),
            array($this->translateCharFromCode('\u10000').':foo', false),
            array($this->translateCharFromCode('\u10FFFF').':foo', false),

            // invalid strange characters in namespace
            array($this->translateCharFromCode('\u0001').':foo', false),
            array($this->translateCharFromCode('\u0002').':foo', false),
            array($this->translateCharFromCode('\u0003').':foo', false),
            array($this->translateCharFromCode('\u0008').':foo', false),
            array($this->translateCharFromCode('\uFFFF').':foo', false),

            // invalid strange characters in name
            array('foo:' . $this->translateCharFromCode('\u0001'), false),
            array('foo:' . $this->translateCharFromCode('\u0002'), false),
            array('foo:' . $this->translateCharFromCode('\u0003'), false),
            array('foo:' . $this->translateCharFromCode('\u0008'), false),
            array('foo:' . $this->translateCharFromCode('\uFFFF'), false),
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
