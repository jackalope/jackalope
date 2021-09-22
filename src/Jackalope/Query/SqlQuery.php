<?php

namespace Jackalope\Query;

/**
 * Query implementation for the SQL2 language.
 *
 * This can never be legally created if the transport does not implement
 * QueryInterface.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
class SqlQuery extends Query
{
    /**
     * Access the query statement from the transport layer.
     *
     * @private
     */
    public function getStatementSql2(): string
    {
        return $this->getStatement();
        // TODO: should this expand bind variables? or the transport?
    }

    /**
     * Returns the constant QueryInterface::JCR-SQL2.
     */
    public function getLanguage(): string
    {
        return self::JCR_SQL2;
    }
}
