<?php

namespace Jackalope\NodeType\Path;

use Jackalope\NodeType\NodeProcessor;
use Jackalope\TestCase;
use PHPCR\PropertyType;
use Jackalope\Validation\PathValidatorTestCase;
use Jackalope\Validation\Path\JackrabbitPathValidator;

class JackrabbitPathValidatorTest extends PathValidatorTestCase
{
    public function getValidator()
    {
        return new JackrabbitPathValidator();
    }

    public function getPathAnswers()
    {
        return array(
            'absolute_1' => true,
            'absolute_2' => false,
            'absolute_3' => false,
            'normal_1' => true,
            'normal_2' => true,
            'normal_3' => true,
            'normal_4' => true,
            'normal_5' => true,
            'normal_6' => true,
            'normal_7' => true,
            'normal_8' => true,
            'normal_9' => true,
            'normal_10' => false,
            'normal_11' => false,
            'normal_12' => false,
            'normal_13' => false,
            'normal_14' => false,
        );
    }

    public function getNameAnswers()
    {
        return array(
            'normal_1' => false,
            'normal_2' => false,
            'normal_3' => true,
            'normal_4' => true,
            'normal_5' => false,
            'normal_6' => true,
            'namespace_1' => true,
            'namespace_2' => true,
            'namespace_3' => true,
            'namespace_4' => true,
            'namespace_5' => false,
            'namespace_6' => false,
            'namespace_7' => false,
            'namespace_8' => false,
            'namespace_9' => false,
            'localname_1' => true,
            'localname_2' => true,
            'localname_3' => true,
            'localname_4' => true,
            'localname_5' => true,
        );
    }
}
