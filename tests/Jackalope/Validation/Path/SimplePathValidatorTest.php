<?php

namespace Jackalope\NodeType;

use Jackalope\Validation\Path\SimplePathValidator;
use Jackalope\Validation\PathValidatorTestCase;

class SimplePathValidatorTest extends PathValidatorTestCase
{
    public function getValidator()
    {
        return new SimplePathValidator();
    }

    public function getPathAnswers()
    {
        return [
            'absolute_1' => true,
            'absolute_2' => false,
            'absolute_3' => false,
            'normal_1' => true,
            'normal_2' => false,
            'normal_3' => false,
            'normal_4' => false,
            'normal_5' => false,
            'normal_6' => false,
            'normal_7' => true,
            'normal_8' => true,
            'normal_9' => false,
            'normal_10' => false,
            'normal_11' => false,
            'normal_12' => false,
            'normal_13' => false,
            'normal_14' => false,
        ];
    }

    public function getNameAnswers()
    {
        return [
            'normal_1' => false,
            'normal_2' => false,
            'normal_3' => true,
            'normal_4' => false,
            'normal_5' => false,
            'normal_6' => true,
            'namespace_1' => false,
            'namespace_2' => false,
            'namespace_3' => false,
            'namespace_4' => false,
            'namespace_5' => false,
            'namespace_6' => false,
            'namespace_7' => false,
            'namespace_8' => false,
            'namespace_9' => false,
            'localname_1' => false,
            'localname_2' => false,
            'localname_3' => false,
            'localname_4' => false,
            'localname_5' => false,
        ];
    }
}
