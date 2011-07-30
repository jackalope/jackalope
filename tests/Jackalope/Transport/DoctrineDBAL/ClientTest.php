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

        $this->transport = new \Jackalope\Transport\DoctrineDBAL\Client(new \Jackalope\Factory(), $conn);
        $this->transport->createWorkspace('default');

        $this->repository = new \Jackalope\Repository(null, null, $this->transport);
        $this->session = $this->repository->login(new \PHPCR\SimpleCredentials("user", "passwd"), "default");
    }

    public function testQueryNodes()
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

    public function testAddNodeTypes()
    {
        $workspace = $this->session->getWorkspace();
        $ntm = $workspace->getNodeTypeManager();
        $template = $ntm->createNodeTypeTemplate();
        $template->setName('phpcr:article');
        
        $propertyDefs = $template->getPropertyDefinitionTemplates();
        $propertyTemplate = $ntm->createPropertyDefinitionTemplate();
        $propertyTemplate->setName('headline');
        $propertyTemplate->setRequiredType(\PHPCR\PropertyType::STRING);
        $propertyDefs[] = $propertyTemplate;

        $childDefs = $template->getNodeDefinitionTemplates();
        $nodeTemplate = $ntm->createNodeDefinitionTemplate();
        $nodeTemplate->setName('article_content');
        $nodeTemplate->setDefaultPrimaryTypeName('nt:unstructured');
        $nodeTemplate->setMandatory(true);
        $childDefs[] = $nodeTemplate;

        $ntm->registerNodeTypes(array($template), true);
        
        $def = $ntm->getNodeType('phpcr:article');
        $this->assertEquals("phpcr:article", $def->getName());
        $this->assertEquals(1, count($def->getDeclaredPropertyDefinitions()));
        $this->assertEquals(1, count($def->getDeclaredChildNodeDefinitions()));
    }
}
