<?php
namespace Jackalope\Query;

use Jackalope\ObjectManager;

/**
 * Query implementation for the SQL2 language
 */
class SqlQuery implements \PHPCR\Query\QueryInterface
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
     * @var \Jackalope\ObjectManager
     */
    protected $objectManager;
    /**
     * If this is a stored query, the path to the node that stores this query.
     * @var string
     */
    protected $path;

    /**
     * Create a new SQL2 query instance
     *
     * @param object $factory an object factory implementing "get" as described
     *      in \Jackalope\Factory
     * @param string $statement The SQL statement for this query
     * @param ObjectManager $objectManager Object manager to execute query
     *      against
     * @param string $path If this query is loaded from workspace with
     *      QueryManager::getQuery(), path has to be provided here
     */
    public function __construct($factory, $statement, ObjectManager $objectManager, $path = null)
    {
        $this->factory = $factory;
        $this->statement = $statement;
        $this->objectManager = $objectManager;
        $this->path = $path;
    }

    // inherit all doc
    /**
     * @api
     */
    public function bindValue($varName, $value)
    {
        throw new \PHPCR\RepositoryException('Not Implemented...');
    }

    // inherit all doc
    /**
     * @api
     */
    public function execute()
    {
        $transport = $this->objectManager->getTransport();
        $rawData = $transport->query($this);
        $queryResult = $this->factory->get(
            'Query\QueryResult',
            array(
                $rawData,
                $this->objectManager,
            )
        );
        return $queryResult;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getBindVariableNames()
    {
        throw new \PHPCR\RepositoryException('Not Implemented...');
    }

    // inherit all doc
    /**
     * @api
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * Access the limit from the transport layer
     *
     * @return the limit set with setLimit
     */
    public function getLimit()
    {
        return $this->limit;
    }

    // inherit all doc
    /**
     * @api
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
    }

    /**
     * Access the offset from the transport layer
     *
     * @return the offset set with setOffset
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * Access the query statement from the transport layer
     *
     * @return string the sql2 query statement
     *
     * @private
     */
    public function getStatementSql2()
    {
        return $this->statement; //TODO: should this expand bind variables? or the transport?
    }

    // inherit all doc
    /**
     * @api
     */
    public function getStatement()
    {
        return $this->statement;
    }

    /**
     * Returns the constant \PHPCR\QueryInterface::JCR-SQL2
     *
     * @return string the query language.
     */
    public function getLanguage()
    {
       return self::JCR_SQL2;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getStoredQueryPath()
    {
        if ($this->path == null) {
            throw new \PHPCR\ItemNotFoundException('Not a stored query');
        }
        return $this->path;
    }

    // inherit all doc
    /**
     * @api
     */
    public function storeAsNode($absPath)
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException('Not implemented: Write');
    }

}
