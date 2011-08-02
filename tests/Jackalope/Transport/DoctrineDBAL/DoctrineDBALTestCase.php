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
            $this->markTestSkipped('phpcr.doctrine.loaded is not set. Skipping Doctrine tests.');
        }
    }

    protected function getConnection()
    {
        if ($this->conn === null) {
            $this->conn = DriverManager::getConnection(array(
                'driver'    => $GLOBALS['phpcr.doctrine.dbal.driver'],
                'user'      => $GLOBALS['phpcr.doctrine.dbal.username'],
                'password'  => $GLOBALS['phpcr.doctrine.dbal.password'],
                'dbname'    => $GLOBALS['phpcr.doctrine.dbal.dbname'],
                'host'      => $GLOBALS['phpcr.doctrine.dbal.host'],
            ));
        }
        return $this->conn;
    }
}