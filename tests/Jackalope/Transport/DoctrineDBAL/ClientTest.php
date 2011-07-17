<?php

namespace Jackalope\Transport\DoctrineDBAL;

use Doctrine\DBAL\DriverManager;

class ClientTest extends DoctrineDBALTestCase
{
    private $transport;

    public function setUp()
    {
        parent::setUp();
        
        $conn = $this->getConnection();
        $schema = RepositorySchema::create();
        $sql = $schema->toSql($conn->getDatabasePlatform());
        foreach ($sql AS $statement) {
            $conn->exec($statement);
        }
        $this->markTestSkipped("Refactoring necessary");

        $conn->insert("phpcr_workspaces", array("name" => "Test"));
        $workspaceId = $conn->lastInsertId();
        $conn->insert("phpcr_nodes", array("path" => "", "workspace_id" => $workspaceId, 'parent' => '-1', "type" => "nt:unstructured", "identifier" => 1));
        $parentId = $this->conn->lastInsertId();
        $this->conn->insert("phpcr_nodes", array("path" => "foo", "workspace_id" => $workspaceId, 'parent' => $parentId, "type" => "nt:unstructured", "identifier" => 2));
        $this->conn->insert("phpcr_props", array(
            "path" => "foo/bar", "workspace_id" => $workspaceId, "type" => \PHPCR\PropertyType::STRING,
            "node_identifier" => 2, "string_data" => "test", "name" => "bar"));

        $this->transport = new \Jackalope\Transport\DoctrineDBAL\Client(new \Jackalope\Factory(), $this->conn);
    }

    public function testStuff()
    {
        $this->transport->login(new \PHPCR\GuestCredentials(), "Test");
        $foo = $this->transport->getNode("foo");
        $this->assertEquals(2, $foo->{'jcr:uuid'});
    }
}
