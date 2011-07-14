<?php

namespace Jackalope\Transport\DoctrineDBAL;

use Jackalope\TestCase;
use Doctrine\DBAL\DriverManager;

abstract class DoctrineDBALTestCase extends TestCase
{
    public function setUp()
    {
        if (!isset($GLOBALS['phpcr.doctrine.loaded'])) {
            $this->markTestSkipped('phpcr.doctrine.loader and phpcr.doctrine.dbaldir are not configured. Skipping Doctrine tests.');
        }
    }
}