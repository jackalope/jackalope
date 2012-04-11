<?php

namespace Jackalope\Query;

use PHPCR\UnsupportedRepositoryOperationException;
use PHPCR\RepositoryException;
use PHPCR\ItemNotFoundException;
use PHPCR\Query\QueryInterface;

use Jackalope\ObjectManager;
use Jackalope\FactoryInterface;

/**
 * Query implementation for the XPATH language
 *
 * This can never be legally created if the transport does not implement
 * QueryInterface
 */
class XpathQuery extends SqlQuery
{
    /**
     * The factory to instantiate objects
     * @var Factory
     */
    protected $factory;
    /**
     * The sql2 query statement
     * @var string
     */
    protected $statement;
    /**
     * Limit for the query
     * @var integer
     */
    protected $limit;
    /**
     * Offset to start results from
     * @var integer
     */
    protected $offset;
    /**
     * The object manager to execute the query with.
     *
     * @var \Jackalope\ObjectManager
     */
    protected $objectManager;
    /**
     * If this is a stored query, the path to the node that stores this query.
     * @var string
     */
    protected $path;

    /**
     * Create a new XPath query instance
     *
     * @param FactoryInterface $factory the object factory
     * @param string $statement The SQL statement for this query
     * @param ObjectManager $objectManager (can be omitted if you do not want
     *      to execute the query but just use it with a parser)
     * @param string $path If this query is loaded from workspace with
     *      QueryManager::getQuery(), path has to be provided here
     */
    public function __construct(FactoryInterface $factory, $statement, ObjectManager $objectManager = null, $path = null)
    {
        $this->factory = $factory;
        $this->statement = $statement;
        $this->objectManager = $objectManager;
        $this->path = $path;
    }

    /**
     * Access the query statement from the transport layer
     *
     * @return string the sql2 query statement
     *
     * @private
     */
    public function getStatementXpath()
    {
        return $this->getStatement();
        //TODO: should this expand bind variables? or the transport?
    }

    /**
     * Returns the constant QueryInterface::JCR-SQL2
     *
     * @return string the query language.
     */
    public function getLanguage()
    {
       return self::JCR_XPATH;
    }

}
