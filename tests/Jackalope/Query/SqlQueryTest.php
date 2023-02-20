<?php

namespace Jackalope\Query;

use Jackalope\Factory;
use Jackalope\FactoryInterface;
use Jackalope\ObjectManager;
use Jackalope\TestCase;
use Jackalope\Transport\QueryInterface;
use PHPCR\ItemNotFoundException;
use PHPCR\Query\QueryResultInterface;

class SqlQueryTest extends TestCase
{
    protected string $statement = 'statement';

    protected function getQuery($factory = null, $statement = null, $objectManager = null, $path = null): SqlQuery
    {
        if (!$factory) {
            $factory = new Factory();
        }
        if (!$statement) {
            $statement = $this->statement;
        }
        if (!$objectManager) {
            $objectManager = $this->createMock(ObjectManager::class);
        }

        return new SqlQuery($factory, $statement, $objectManager, $path);
    }

    public function testBindValue(): void
    {
        $this->markTestSkipped('TODO: implement');
    }

    public function testExecute(): void
    {
        $dummyData = ['x'];
        $factory = $this->createMock(FactoryInterface::class);
        $transport = $this->createMock(QueryInterface::class);

        $om = $this->createMock(ObjectManager::class);
        $om
            ->method('getTransport')
            ->willReturn($transport)
        ;

        $query = $this->getQuery($factory, null, $om);

        $transport->expects($this->once())
            ->method('query')
            ->with($query)
            ->willReturn($dummyData)
        ;

        $queryResult = $this->createMock(QueryResultInterface::class);
        $factory->expects($this->once())
                ->method('get')
                ->with(QueryResult::class, [$dummyData, $om])
                ->willReturn($queryResult)
        ;

        $result = $query->execute();
        $this->assertSame($queryResult, $result);
    }

    public function testGetBindVariableNames(): void
    {
        $this->markTestSkipped('TODO: implement');
    }

    public function testLimit(): void
    {
        $query = $this->getQuery();
        $query->setLimit(37);
        $this->assertEquals(37, $query->getLimit());
    }

    public function testOffset(): void
    {
        $query = $this->getQuery();
        $query->setOffset(15);
        $this->assertEquals(15, $query->getOffset());
    }

    public function testGetStatementSql2(): void
    {
        $query = $this->getQuery();
        $this->assertEquals($this->statement, $query->getStatementSql2());
    }

    public function testGetStatement(): void
    {
        $query = $this->getQuery();
        $this->assertEquals($this->statement, $query->getStatement());
    }

    public function testGetLanguage(): void
    {
        $query = $this->getQuery();
        $this->assertEquals(\PHPCR\Query\QueryInterface::JCR_SQL2, $query->getLanguage());
    }

    public function testGetStoredQueryPath(): void
    {
        $query = $this->getQuery(null, null, null, '/path/query');
        $this->assertSame('/path/query', $query->getStoredQueryPath());
    }

    public function testGetStoredQueryPathNotStored(): void
    {
        $this->expectException(ItemNotFoundException::class);

        $query = $this->getQuery();
        $query->getStoredQueryPath();
    }

    public function testStoreAsNode(): void
    {
        $this->markTestSkipped('TODO: implement feature');
    }
}
