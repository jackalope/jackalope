<?php

namespace Jackalope\Transport\DoctrineDBAL;

use Doctrine\DBAL\DriverManager;

class ClientTest extends DoctrineDBALTestCase
{
    private $transport;
    /**
     * @var \Jackalope\Repository
     */
    private $repository;
    /**
     * @var \Jackalope\Session
     */
    private $session;

    public function setUp()
    {
        parent::setUp();
        
        $conn = $this->getConnection();
        $schema = RepositorySchema::create();

        foreach ($schema->toDropSql($conn->getDatabasePlatform()) AS $statement) {
            try {
                $conn->exec($statement);
            } catch(\Exception $e) {

            }
        }

        foreach ($schema->toSql($conn->getDatabasePlatform()) AS $statement) {
            $conn->exec($statement);
        }

        $this->transport = new \Jackalope\Transport\DoctrineDBAL\Client(new \Jackalope\Factory(), $this->conn);
        $this->transport->createWorkspace('default');

        $this->repository = new \Jackalope\Repository(null, null, $this->transport);
        $this->session = $this->repository->login(new \PHPCR\SimpleCredentials("user", "passwd"), "default");
    }

    public function testFunctional()
    {
        $root = $this->session->getNode('/');
        $article = $root->addNode('article');
        $article->setProperty('foo', 'bar');
        $article->setProperty('bar', 'baz');

        $this->session->save();

        $qm = $this->session->getWorkspace()->getQueryManager();
        $query = $qm->createQuery('SELECT * FROM [nt:unstructured]', \PHPCR\Query\QueryInterface::JCR_SQL2);
        $result = $query->execute();

        $this->assertEquals(2, count($result->getNodes()));

        $query = $qm->createQuery('SELECT * FROM [nt:unstructured] WHERE foo = "bar"', \PHPCR\Query\QueryInterface::JCR_SQL2);
        $result = $query->execute();

        $this->assertEquals(1, count($result->getNodes()));
    }
}
