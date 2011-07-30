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
                'driver'    => $GLOBALS['phpcr.dbal.driver'],
                'user'      => $GLOBALS['phpcr.dbal.user'],
                'password'  => $GLOBALS['phpcr.dbal.pass'],
                'dbname'    => $GLOBALS['phpcr.dbal.dbname'],
                'host'      => $GLOBALS['phpcr.dbal.host'],
            ));
        }
        return $this->conn;
    }
}