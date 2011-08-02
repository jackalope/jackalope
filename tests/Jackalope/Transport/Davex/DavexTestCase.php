<?php

namespace Jackalope\Transport\Davex;

use Jackalope\TestCase;

abstract class DavexTestCase extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        if (! isset($GLOBALS['jackrabbit.uri'])) {
            throw new \PHPUnit_Framework_SkippedTestSuiteError('jackrabbit.uri is not set. Skipping jackrabbit specific tests');
        }
        $this->config['url'] = $GLOBALS['jackrabbit.uri'];
    }
}