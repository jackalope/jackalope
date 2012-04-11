<?php

namespace Jackalope\Query;

/**
 * Query implementation for the SQL2 language
 *
 * This can never be legally created if the transport does not implement
 * QueryInterface
 */
class SQLQuery extends Query
{

    /**
     * Access the query statement from the transport layer
     *
     * @return string the xpath query statement
     *
     * @private
     */
    public function getStatementSql2()
    {
        return $this->getStatement();
        //TODO: should this expand bind variables? or the transport?
    }

    /**
     * Returns the constant QueryInterface::JCR-XPATH
     *
     * @return string the query language.
     */
    public function getLanguage()
    {
       return self::JCR_SQL2;
    }

}
