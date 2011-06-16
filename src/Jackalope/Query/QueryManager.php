<?php
namespace Jackalope\Query;

use Jackalope\ObjectManager, Jackalope\NotImplementedException;

/**
 * This interface encapsulates methods for the management of search queries.
 * Provides methods for the creation and retrieval of search queries.
 */
class QueryManager implements \PHPCR\Query\QueryManagerInterface
{
    /**
     * The factory to instantiate objects
     * @var Factory
     */
    protected $factory;

    protected $objectmanager;

    public function __construct($factory, ObjectManager $objectmanager)
    {
        $this->factory = $factory;
        $this->objectmanager = $objectmanager;
    }
    /**
     * Creates a new query by specifying the query statement itself and the language
     * in which the query is stated. The $language must be a string from among
     * those returned by QueryManager.getSupportedQueryLanguages().
     *
     * @param string $statement
     * @param string $language
     * @return \PHPCR\Query\QueryInterface a Query object
     * @throws \PHPCR\Query\InvalidQueryException if the query statement is syntactically invalid or the specified language is not supported
     * @throws \PHPCR\RepositoryException if another error occurs
     * @api
     */
    public function createQuery($statement, $language)
    {
        switch($language) {
            case \PHPCR\Query\QueryInterface::JCR_SQL2:
                return $this->factory->get('Query\SqlQuery', array($statement, $this->objectmanager));
            case \PHPCR\Query\QueryInterface::JCR_JQOM:
                throw new NotImplementedException();
            default:
                throw new \PHPCR\Query\InvalidQueryException("No such query language: $language");
        }
    }

    /**
     * Returns a QueryObjectModelFactory with which a JCR-JQOM query can be built
     * programmatically.
     *
     * @return \PHPCR\Query\QOM\QueryObjectModelFactoryInterface a QueryObjectModelFactory object
     * @api
     */
    public function getQOMFactory()
    {
        return new \Jackalope\Query\QOM\QueryObjectModelFactory();
    }

    /**
     * Retrieves an existing persistent query.
     *
     * @param \PHPCR\NodeInterface $node a persisted query (that is, a node of type nt:query).
     * @return \PHPCR\Query\QueryInterface a Query object.
     * @throws \PHPCR\Query\InvalidQueryException If node is not a valid persisted query (that is, a node of type nt:query).
     * @throws \PHPCR\RepositoryException if another error occurs
     * @api
     */
    public function getQuery($node)
    {
        throw new NotImplementedException();
    }

    /**
     * Supports Query.JCR_SQL2 and Query.JCR_JQOM
     *
     * @return array A string array.
     * @throws \PHPCR\RepositoryException if an error occurs.
     * @api
     */
    public function getSupportedQueryLanguages()
    {
        return array(\PHPCR\Query\QueryInterface::JCR_SQL2, \PHPCR\Query\QueryInterface::JCR_JQOM);
    }
}
