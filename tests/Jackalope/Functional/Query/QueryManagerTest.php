<?php
namespace Jackalope\Functional\Query;

require_once(__DIR__.'/../../../phpcr-api/tests/06_Query/QueryBaseCase.php');

use PHPCR\Tests\Query\QueryBaseCase;

/**
 * xpath tests for the query manager
 */
class QueryManagerTest extends QueryBaseCase
{
    /*
    public static function setupBeforeClass($fixture = 'general/query')
    {
        parent::setupBeforeClass($fixture);
    }
    */
    public function setUp()
    {
        $this->markTestSkipped('TODO: add support for xpath in jackalope');
    }

    public function testCreateXpathQuery()
    {
        //$this->sharedFixture['qm']->createQuery('/jcr:root', ?);
    }
}
