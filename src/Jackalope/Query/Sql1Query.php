<?php

namespace Jackalope\Query;

/**
 * Query implementation for the (deprecated) SQL(1) language.
 *
 * This can never be legally created if the transport does not implement
 * QueryInterface.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
class Sql1Query extends Query
{

    /**
     * Access the query statement from the transport layer
     *
     * @return string the sql1 query statement
     *
     * @private
     */
    public function getStatementSql()
    {
        return $this->getStatement();
        //TODO: should this expand bind variables? or the transport?
    }

    /**
     * Returns the constant QueryInterface::JCR-SQL
     *
     * @return string the query language.
     */
    public function getLanguage()
    {
       return self::SQL;
    }

}
