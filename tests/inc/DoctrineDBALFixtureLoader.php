<?php

require_once __DIR__.'/../phpcr-api/inc/FixtureLoaderInterface.php';

/**
 * Import fixtures into the doctrine dbal backend of jackalope
 */
class DoctrineDBALFixtureLoader implements \PHPCR\Test\FixtureLoaderInterface
{
    private $testConn;
    private $fixturePath;

    public function __construct($conn, $fixturePath)
    {
        $this->testConn = new \PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection($conn, "tests");
        $this->fixturePath = $fixturePath;
    }

    public function import($file)
    {
        $file = $this->fixturePath . $file . ".xml";

        if (!file_exists($file)) {
            throw new PHPUnit_Framework_SkippedTestSuiteError("No fixtures $file, skipping this test suite"); // TODO: should we not do something that stops the tests from running? this is a very fundamental problem.
        }

        $dataSet = new PHPUnit_Extensions_Database_DataSet_XmlDataSet($file);

        $tester = new PHPUnit_Extensions_Database_DefaultTester($this->testConn);
        $tester->setSetUpOperation(PHPUnit_Extensions_Database_Operation_Factory::CLEAN_INSERT());
        $tester->setTearDownOperation(PHPUnit_Extensions_Database_Operation_Factory::NONE());
        $tester->setDataSet($dataSet);
        try {
            $tester->onSetUp();
        } catch(PHPUnit_Extensions_Database_Operation_Exception $e) {
            throw new RuntimeException("Could not load fixture ".$file.": ".$e->getMessage());
        }
    }
}
