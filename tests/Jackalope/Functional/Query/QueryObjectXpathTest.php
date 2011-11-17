<?php
namespace PHPCR\Tests\Query;

require_once(__DIR__.'/../../../phpcr-api/tests/06_Query/QueryBaseCase.php');

use PHPCR\Tests\Query\QueryBaseCase;

/**
 * test the xpath functionality
 *
 * setLimit, setOffset, bindValue, getBindVariableNames
 *
 * TODO: make xpath possible in jackalope if the backend supports it and make this test work again
 */
class QueryObjectXpathTest extends QueryBaseCase
{
    public function setUp()
    {
        $this->markTestSkipped('TODO: add support for xpath in jackalope');
        parent::setUp();
    }
    public function testExecute()
    {
        $query = $this->sharedFixture['qm']->createQuery('//idExample[jcr:mimeType="text/plain"]', 'xpath');
        $qr = $query->execute();
        $this->assertInstanceOf('PHPCR\Query\QueryResultInterface', $qr);
        //content of result is tested in QueryResults
    }

    /**
     * @expectedException PHPCR\Query\InvalidQueryException
     *
     * the doc claims there would just be a PHPCR\RepositoryException
     * it makes sense that there is a InvalidQueryException
     */
    public function testExecuteInvalid()
    {
        $query = $this->sharedFixture['qm']->createQuery('this is no xpath statement', 'xpath');
        $qr = $query->execute();
    }

    public function testGetStatement()
    {
        $qstr = '//idExample[jcr:mimeType="text/plain"]';
        $query = $this->sharedFixture['qm']->createQuery($qstr, 'xpath');
        $this->assertEquals($qstr, $query->getStatement());
    }
    public function testGetLanguage()
    {
        $qstr = '//idExample[jcr:mimeType="text/plain"]';
        $query = $this->sharedFixture['qm']->createQuery($qstr, 'xpath');
        $this->assertEquals('xpath', $query->getLanguage());
    }
    /**
     * a transient query has no stored query path
     * @expectedException PHPCR\ItemNotFoundException
     */
    public function testGetStoredQueryPathItemNotFound()
    {
        $qstr = '//idExample[jcr:mimeType="text/plain"]';
        $query = $this->sharedFixture['qm']->createQuery($qstr, 'xpath');
        $query->getStoredQueryPath();
    }
    /* this is level 2 only */
    /*
    public function testStoreAsNode()
    {
        $qstr = '//idExample[jcr:mimeType="text/plain"]';
        $query = $this->sharedFixture['qm']->createQuery($qstr, 'xpath');
        $query->storeAsNode('/queryNode');
        $this->sharedFixture['session']->save();
    }
    */
    /*
    +diverse exceptions
    */

    /** changes repository state */
    public function testGetStoredQueryPath()
    {
        $this->sharedFixture['ie']->import('general/query');
        try {
            $qnode = $this->sharedFixture['session']->getRootNode()->getNode('queryNode');
            $this->assertInstanceOf('PHPCR\NodeInterface', $qnode);

            $query = $this->sharedFixture['qm']->getQuery($qnode);
            $this->assertInstanceOf('PHPCR\Query\QueryInterface', $query);
            //same as QueryManager::testGetQuery

            $p = $query->getStoredQueryPath();
            $this->assertEquals('/queryNode', $p);
        } catch(exception $e) {
            //FIXME: finally?
            $this->sharedFixture['ie']->import('general/base');
            throw $e;
        }
        $this->sharedFixture['ie']->import('read/search/base');
    }

}
