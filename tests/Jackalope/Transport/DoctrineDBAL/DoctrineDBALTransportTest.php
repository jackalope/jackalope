<?php

namespace Jackalope\Transport\DoctrineDBAL;

use Jackalope\TestCase;
use Doctrine\DBAL\DriverManager;

class DoctrineDBALTransportTest extends TestCase
{
    private $conn;
    private $transport;

    public function setUp()
    {
        if (!isset($GLOBALS['phpcr.doctrine.loaded'])) {
            $this->markTestSkipped('phpcr.doctrine.loader and phpcr.doctrine.dbaldir are not configured. Skipping Doctrine tests.');
        }
        $this->conn = DriverManager::getConnection(array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ));
        $schema = RepositorySchema::create();
        $sql = $schema->toSql($this->conn->getDatabasePlatform());
        foreach ($sql AS $statement) {
            echo $statement."\n\n";
            $this->conn->exec($statement);
        }

        $this->conn->insert("jcrworkspaces", array("name" => "Test"));
        $workspaceId = $this->conn->lastInsertId();
        $this->conn->insert("jcrnodes", array("path" => "", "workspace_id" => $workspaceId, "type" => "nt:unstructured", "identifier" => 1));
        $this->conn->insert("jcrnodes", array("path" => "foo", "workspace_id" => $workspaceId, "type" => "nt:unstructured", "identifier" => 2));
        $this->conn->insert("jcrprops", array(
            "path" => "foo/bar", "workspace_id" => $workspaceId, "type" => \PHPCR\PropertyType::STRING,
            "node_identifier" => 2, "string_data" => "test", "name" => "bar"));

        $this->transport = new \Jackalope\Transport\DoctrineDBAL($this->conn);
    }

    public function testStuff()
    {
        $this->transport->login(new \PHPCR\GuestCredentials(), "Test");
        var_dump($this->transport->getItem("foo"));
    }

    public function testFunctional()
    {
        
    }
}
