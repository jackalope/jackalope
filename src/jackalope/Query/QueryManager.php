<?php
/**
 * This interface encapsulates methods for the management of search queries.
 * Provides methods for the creation and retrieval of search queries.
 */
class jackalope_Query_QueryManager implements PHPCR_Query_QueryManagerInterface {
    protected $objectmanager;

    public function __construct(jackalope_ObjectManager $objectmanager) {
        $this->objectmanager = $objectmanager;
    }
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
        switch($language) {
            case PHPCR_Query_QueryInterface::JCR_SQL2:
                return new jackalope_Query_SqlQuery($statement, $this->objectmanager);
            case PHPCR_Query_QueryInterface::JCR_JQOM:
                throw new jackalope_NotImplementedException();
            default:
                throw new PHPCR_Query_InvalidQueryException("No such query language: $language");
        }
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
        return array(PHPCR_Query_QueryInterface::JCR_SQL2, PHPCR_Query_QueryInterface::JCR_JQOM);
    }
}
