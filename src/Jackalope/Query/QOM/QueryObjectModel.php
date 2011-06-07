<?php

namespace Jackalope\Query\QOM;

use PHPCR\Query\QOM\QueryObjectModelInterface;
use PHPCR\Query\QOM\SourceInterface;

/**
 * A query in the JCR query object model.
 *
 * The JCR query object model describes the queries that can be evaluated by a JCR
 * repository independent of any particular query language, such as SQL.
 *
 * A query consists of:
 *
 * - a source. When the query is evaluated, the source evaluates its selectors and
 *   the joins between them to produce a (possibly empty) set of node-tuples. This
 *   is a set of 1-tuples if the query has one selector (and therefore no joins), a
 *   set of 2-tuples if the query has two selectors (and therefore one join), a set
 *   of 3-tuples if the query has three selectors (two joins), and so forth.
 * - an optional constraint. When the query is evaluated, the constraint filters the
 *   set of node-tuples.
 * - a list of zero or more orderings. The orderings specify the order in which the
 *   node-tuples appear in the query results. The relative order of two node-tuples
 *   is determined by evaluating the specified orderings, in list order, until
 *   encountering an ordering for which one node-tuple precedes the other. If no
 *   orderings are specified, or if for none of the specified orderings does one
 *   node-tuple precede the other, then the relative order of the node-tuples is
 *   implementation determined (and may be arbitrary).
 * - a list of zero or more columns to include in the tabular view of the query
 *   results. If no columns are specified, the columns available in the tabular view
 *   are implementation determined, but minimally include, for each selector, a column
 *   for each single-valued non-residual property of the selector's node type.
 *
 * The query object model representation of a query is created by factory methods in the QueryObjectModelFactory.
 *
 * 
 * @api
 */
class QueryObjectModel implements QueryObjectModelInterface
{
    /**
     * @var \PHPCR\Query\QOM\SourceInterface
     */
    protected $source;

    /**
     * @var \PHPCR\Query\QOM\ConstraintInterface
     */
    protected $constraint;

    /**
     * @var array
     */
    protected $orderings;

    /**
     * @var array
     */
    protected $columns;

    /**
     * Constructor
     *
     * @param SourceInterface $source
     * @param ConstraintInterface $constraint
     * @param array $orderings
     * @param array $columns 
     */
    public function __construct(SourceInterface $source, $constraint, array $orderings, array $columns)
    {
        $this->source = $source;
        $this->constraint = $constraint;
        $this->orderings = $orderings;
        $this->columns = $columns;
    }

    /**
     * {@inheritdoc}
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * {@inheritdoc}
     */
    function getConstraint()
    {
        return $this->constraint;
    }

    /**
     * {@inheritdoc}
     */
    function getOrderings()
    {
        return $this->orderings;
    }

    /**
     * {@inheritdoc}
     */
    function getColumns()
    {
        return $this->columns;
    }

    /**
     * Binds the given value to the variable named $varName.
     *
     * @param string $varName name of variable in query
     * @param mixed $value value to bind
     * @return void
     *
     * @throws \InvalidArgumentException if $varName is not a valid variable in this query.
     * @throws RepositoryException if an error occurs.
     * @api
     */
    function bindValue($varName, $value)
    {
        throw new \Jackalope\NotImplementedException();
    }

    /**
     * Executes this query and returns a QueryResult object.
     *
     * @return \PHPCR\Query\QueryInterface a QueryResult object
     *
     * @throws \PHPCR\Query\InvalidQueryException if the query contains an unbound variable.
     * @throws \PHPCR\RepositoryException if an error occurs
     * @api
     */
    function execute()
    {
        throw new \Jackalope\NotImplementedException();
    }

    /**
     * Returns the names of the bind variables in this query.
     *
     * If this query does not contains any bind variables then an empty array is returned.
     *
     * @return array the names of the bind variables in this query.
     *
     * @throws \PHPCR\RepositoryException if an error occurs.
     * @api
     */
    function getBindVariableNames()
    {
        throw new \Jackalope\NotImplementedException();
    }

    /**
     * Sets the maximum size of the result set to limit.
     *
     * @param integer $limit The amount of result items to be fetched.
     * @return void
     * @api
     */
    function setLimit($limit)
    {
        throw new \Jackalope\NotImplementedException();
    }

    /**
     * Sets the start offset of the result set to offset.
     *
     * @param integer $offset The start point of the result set from when the item shall be fetched.
     * @return void
     * @api
     */
    function setOffset($offset)
    {
        throw new \Jackalope\NotImplementedException();
    }

    /**
     * Returns the statement defined for this query.
     *
     * If the language of this query is string-based (like JCR-SQL2), this method
     * will return the statement that was used to create this query.
     *
     * If the language of this query is JCR-JQOM, this method will return the
     * JCR-SQL2 equivalent of the JCR-JQOM object tree.
     *
     * This is the standard serialization of JCR-JQOM and is also the string stored
     * in the jcr:statement property if the query is persisted. See storeAsNode($absPath).
     *
     * @return string The query statement.
     * @api
     */
    function getStatement()
    {
        throw new \Jackalope\NotImplementedException();
    }

    /**
     * Returns the language set for this query.
     *
     * This will be one of the query language constants returned by
     * QueryManager.getSupportedQueryLanguages().
     *
     * @return string The query language.
     * @api
     */
    function getLanguage()
    {
        throw new \Jackalope\NotImplementedException();
    }

    /**
     * Fetches the path of the node representing this query.
     *
     * If this is a Query object that has been stored using storeAsNode(java.lang.String)
     * (regardless of whether it has been saved yet) or retrieved using
     * QueryManager.getQuery(javax.jcr.Node)), then this method returns the path
     * of the nt:query node that stores the query.
     *
     * @return string Path of the node representing this query.
     *
     * @throws \PHPCR\ItemNotFoundException if this query is not a stored query.
     * @throws \PHPCR\RepositoryException if another error occurs.
     * @api
     */
    function getStoredQueryPath()
    {
        throw new \Jackalope\NotImplementedException();
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
     *
     * @throws \PHPCR\ItemExistsException if an item at the specified path already exists,
     *                                    same-name siblings are not allowed and this implementation performs
     *                                    this validation immediately.
     * @throws \PHPCR\PathNotFoundException if the specified path implies intermediary Nodes that do not exist
     *                                      or the last element of relPath has an index, and this implementation
     *                                      performs this validation immediately.
     * @throws \PHPCR\NodeType\ConstraintViolationException if a node type or implementation-specific constraint
     *                                                      is violated or if an attempt is made to add a node as
     *                                                      the child of a property and this implementation
     *                                                      performs this validation immediately.
     * @throws \PHPCR\Version\VersionException if the node to which the new child is being added is read-only due to
     *                                         a checked-in node and this implementation performs this validation
     *                                         immediately.
     * @throws \PHPCR\Lock\LockException if a lock prevents the addition of the node and this implementation performs
     *                                   this validation immediately instead of waiting until save.
     * @throws \PHPCR\UnsupportedRepositoryOperationException in a level 1 implementation.
     * @throws \PHPCR\RepositoryException if another error occurs or if the absPath provided has an index on its final
     *                                    element.
     * @api
     */
    function storeAsNode($absPath)
    {
        throw new \Jackalope\NotImplementedException();
    }
}