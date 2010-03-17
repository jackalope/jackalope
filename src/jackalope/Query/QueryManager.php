<?php
/**
 * This interface encapsulates methods for the management of search queries.
 * Provides methods for the creation and retrieval of search queries.
 */
interface jackalope_Query_QueryManager {

    /**
     * Creates a new query by specifying the query statement itself and the language
     * in which the query is stated. The $language must be a string from among
     * those returned by QueryManager.getSupportedQueryLanguages().
     *
     * @param string $statement
     * @param string $language
     * @return PHPCR_Query_QueryInterface a Query object
     * @throws PHPCR_Query_InvalidQueryException if the query statement is syntactically invalid or the specified language is not supported
     * @throws PHPCR_RepositoryException if another error occurs
     * @api
     */
    public function createQuery($statement, $language) {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Returns a QueryObjectModelFactory with which a JCR-JQOM query can be built
     * programmatically.
     *
     * @return PHPCR_Query_QOM_QueryObjectModelFactoryInterface a QueryObjectModelFactory object
     * @api
     */
    public function getQOMFactory() {
        throw new jackalope_NotImplementedException();
    }

    /*
     * Retrieves an existing persistent query.
     *
     * Persistent queries are created by first using QueryManager.createQuery to
     * create a Query object and then calling Query.save to persist the query to
     * a location in the workspace.
     *
     * @param PHPCR_NodeInterface $node a persisted query (that is, a node of type nt:query).
     * @return PHPCR_Query_QueryInterface a Query object.
     * @throws PHPCR_Query_InvalidQueryException If node is not a valid persisted query (that is, a node of type nt:query).
     * @throws PHPCR_RepositoryException if another error occurs
     * @api
     */
    public function getQuery($node) {
        throw new jackalope_NotImplementedException();
    }

    /**
     * Supports Query.JCR_SQL2 and Query.JCR_JQOM
     *
     * @return array A string array.
     * @throws PHPCR_RepositoryException if an error occurs.
     * @api
     */
    public function getSupportedQueryLanguages() {
        return new array(PHPCR_Query_Query::JCR_SQL2, PHPCR_Query_Query::JCR_JQOM);
    }
}
