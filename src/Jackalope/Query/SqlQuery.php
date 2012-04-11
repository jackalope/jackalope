<?php

namespace Jackalope\Query;

/**
 * Query implementation for the SQL2 language
 *
 * This can never be legally created if the transport does not implement
 * QueryInterface
 */
class SqlQuery extends Query
{

    /**
     * Access the query statement from the transport layer
     *
     * @return string the sql2 query statement
     *
     * @private
     */
    public function getStatementSql2()
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
       return self::JCR_SQL2;
    }

}
