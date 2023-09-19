<?php

namespace Jackalope\Query;

use Jackalope\FactoryInterface;
use Jackalope\NotImplementedException;
use Jackalope\ObjectManager;
use Jackalope\Query\QOM\QueryObjectModelFactory;
use PHPCR\Query\InvalidQueryException;
use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use PHPCR\Query\QueryInterface;
use PHPCR\Query\QueryManagerInterface;

/**
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
final class QueryManager implements QueryManagerInterface
{
    private FactoryInterface $factory;

    private ObjectManager $objectManager;

    public function __construct(FactoryInterface $factory, ObjectManager $objectManager)
    {
        $this->factory = $factory;
        $this->objectManager = $objectManager;
    }

    /**
     * @api
     */
    public function createQuery($statement, $language)
    {
        if (!in_array($language, $this->getSupportedQueryLanguages())) {
            throw new InvalidQueryException("Unsupported query language: $language");
        }

        switch ($language) {
            case QueryInterface::JCR_SQL2:
                return $this->factory->get(SqlQuery::class, [$statement, $this->objectManager]);
            case QueryInterface::XPATH:
                return $this->factory->get(XpathQuery::class, [$statement, $this->objectManager]);
            case QueryInterface::SQL:
                return $this->factory->get(Sql1Query::class, [$statement, $this->objectManager]);
            case QueryInterface::JCR_JQOM:
                throw new InvalidQueryException('Please use getQOMFactory to get the query object model factory. You can not build a QOM query from a string.');
            default:
                throw new InvalidQueryException("Transport supports this query language but jackalope not: $language");
        }
    }

    /**
     * @api
     */
    public function getQOMFactory(): QueryObjectModelFactoryInterface
    {
        return $this->factory->get(QueryObjectModelFactory::class, [$this->objectManager]);
    }

    /**
     * @api
     */
    public function getQuery($node): QueryInterface
    {
        throw new NotImplementedException();
    }

    /**
     * @api
     */
    public function getSupportedQueryLanguages(): array
    {
        // Workspace checks if transport implements QueryInterface
        return $this->objectManager->getTransport()->getSupportedQueryLanguages();
    }
}
