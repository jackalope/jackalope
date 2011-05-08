<?php
namespace Jackalope\Query;

use Jackalope\ObjectManager;

/**
 * SQL2 Query Object
 */
class SqlQuery implements \PHPCR\Query\QueryInterface
{
    /**
     * The factory to instantiate objects
     * @var Factory
     */
    protected $factory;

    protected $statement;
    protected $limit;
    protected $offset;
    protected $objectmanager;
    protected $path;

    /**
     * @param object $factory  an object factory implementing "get" as described in \jackalope\Factory
     * @param TODO:string? $statement The SQL statement for this query
     * @param ObjectManager $objectmanager Object manager to execute query against
     * @param string $path If this query is loaded from workspace with QueryManager->getQuery, path has to be stored here
     */
    public function __construct($factory, $statement, ObjectManager $objectmanager, $path = null)
    {
        $this->factory = $factory;
        $this->statement = $statement;
        $this->objectmanager = $objectmanager;
        $this->path = $path;
    }
    /**
     * Binds the given value to the variable named $varName.
     *
     * @param string $varName name of variable in query
     * @param mixed $value value to bind
     * @return void
     * @throws InvalidArgumentException if $varName is not a valid variable in this query.
     * @throws RepositoryException if an error occurs.
     * @api
     */
    public function bindValue($varName, $value)
    {
        throw new \PHPCR\RepositoryException('Not Implemented...');
    }

    /**
     * Executes this query and returns a QueryResult object.
     *
     * @return \PHPCR\Query\QueryInterface a QueryResult object
     * @throws \PHPCR\Query\InvalidQueryException if the query contains an unbound variable.
     * @throws \PHPCR\RepositoryException if an error occurs
     * @api
     */
    public function execute()
    {
        $transport = $this->objectmanager->getTransport();
        $rawData = $transport->query($this);
        $queryResult = $this->factory->get(
            'Query\QueryResult',
            array(
                $rawData,
                $this->objectmanager,
            )
        );
        return $queryResult;
    }

    /**
     * Returns the names of the bind variables in this query. If this query
     * does not contains any bind variables then an empty array is returned.
     *
     * @return array the names of the bind variables in this query.
     * @throws \PHPCR\RepositoryException if an error occurs.
     * @api
     */
    public function getBindVariableNames()
    {
        throw new \PHPCR\RepositoryException('Not Implemented...');
    }

    /**
     * Sets the maximum size of the result set to limit.
     *
     * @param integer $limit
     * @return void
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

    /**
     * Sets the start offset of the result set to offset.
     *
     * @param integer $offset
     * @return void
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
     */
    public function getStatementSql2()
    {
        return $this->statement; //TODO: should this expand bind variables? or the transport?
    }

    /**
     * Returns the statement defined for this query.
     * If the language of this query is string-based (like JCR-SQL2), this method
     * will return the statement that was used to create this query.
     *
     * If the language of this query is JCR-JQOM, this method will return the
     * JCR-SQL2 equivalent of the JCR-JQOM object tree.
     *
     * This is the standard serialization of JCR-JQOM and is also the string stored
     * in the jcr:statement property if the query is persisted. See storeAsNode($absPath).
     *
     * @return string the query statement.
     * @api
     */
    public function getStatement()
    {
        return $this->statement;
    }

    /**
     * JCR-SQL2
     *
     * @return string the query language.
     */
    public function getLanguage()
    {
       return self::JCR_SQL2;
    }

    /**
     * If this is a Query object that has been stored using storeAsNode(java.lang.String)
     * (regardless of whether it has been saved yet) or retrieved using
     * QueryManager.getQuery(javax.jcr.Node)), then this method returns the path
     * of the nt:query node that stores the query.
     *
     * @return string path of the node representing this query.
     * @throws \PHPCR\ItemNotFoundException if this query is not a stored query.
     * @throws \PHPCR\RepositoryException if another error occurs.
     * @api
     */
    public function getStoredQueryPath()
    {
        if ($this->path == null) {
            throw new \PHPCR\ItemNotFoundException('Not a stored query');
        }
        return $this->path;
    }

    /**
     * Creates a node of type nt:query holding this query at $absPath and
     * returns that node.
     *
     * This is  a session-write method and therefore requires a
     * Session.save() to dispatch the change.
     *
     * The $absPath provided must not have an index on its final element. If
     * ordering is supported by the node type of the parent node then the new
     * node is appended to the end of the child node list.
     *
     * @param string $absPath absolute path the query should be stored at
     * @return \PHPCR\NodeInterface the newly created node.
     * @throws \PHPCR\ItemExistsException if an item at the specified path already exists, same-name siblings are not allowed and this implementation performs this validation immediately.
     * @throws \PHPCR\PathNotFoundException if the specified path implies intermediary Nodes that do not exist or the last element of relPath has an index, and this implementation performs this validation immediately.
     * @throws \PHPCR\NodeType\ConstraintViolationException if a node type or implementation-specific constraint is violated or if an attempt is made to add a node as the child of a property and this implementation performs this validation immediately.
     * @throws \PHPCR\Version\VersionException if the node to which the new child is being added is read-only due to a checked-in node and this implementation performs this validation immediately.
     * @throws \PHPCR\Lock\LockException if a lock prevents the addition of the node and this implementation performs this validation immediately instead of waiting until save.
     * @throws \PHPCR\UnsupportedRepositoryOperationException in a level 1 implementation.
     * @throws \PHPCR\RepositoryException if another error occurs or if the absPath provided has an index on its final element.
     * @api
     */
    public function storeAsNode($absPath)
    {
        throw new \PHPCR\UnsupportedRepositoryOperationException('Level 2');
    }

}
