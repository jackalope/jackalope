<?php

namespace Jackalope\Transport\DoctrineDBAL;

use Jackalope\TestCase;
use Doctrine\DBAL\DriverManager;

abstract class DoctrineDBALTestCase extends TestCase
{
    protected $conn;

    public function setUp()
    {
        if (!isset($GLOBALS['phpcr.doctrine.loaded'])) {
            $this->markTestSkipped('phpcr.doctrine.loader and phpcr.doctrine.dbaldir are not configured. Skipping Doctrine tests.');
        }
    }

    protected function getConnection()
    {
        if ($this->conn === null) {
            $this->conn = DriverManager::getConnection(array(
                'driver' => 'pdo_mysql',
                'user' => $GLOBALS['phpcr.user'],
                'password' => $GLOBALS['phpcr.pass'],
                'dbname' => 'phpcr_tests',
                'host' => 'localhost'
            ));
        }
        return $this->conn;
    }
}