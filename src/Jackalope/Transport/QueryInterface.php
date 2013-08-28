<?php

namespace Jackalope\Transport;

use Jackalope\Query\Query;

/**
 * Defines the methods needed for Query support
 *
 * @see <a href="http://www.day.com/specs/jcr/2.0/6_Query.html">JCR 2.0, chapter 6</a>
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
interface QueryInterface extends TransportInterface
{
    /**
     * Search something with the backend.
     *
     * The language must be among those returned by getSupportedQueryLanguages
     *
     * Implementors: Expose all information required by the transport layers to
     * execute the query with getters.
     *
     * array(
     *     //row 1
     *     array(
     *         //column1
     *         array('dcr:name' => 'value1',
     *               'dcr:value' => 'value2',
     *               'dcr:selectorName' => 'value3' //optional
     *         ),
     *         //column 2...
     *     ),
     *     //row 2
     *     array(...
     * )
     *
     * @param Query $query the query object
     *
     * @return array data with search result. TODO: what to return? should be some simple array
     *
     * @see \Jackalope\Query\QueryResult::__construct() for the xml format. TODO: have the transport return a QueryResult?
     */
    public function query(Query $query);

    /**
     * The transport must at least support JCR_SQL2 and JCR_JQOM.
     *
     * Note that QueryObjectModel::getStatement() returns the query as JCR_SQL2
     * so it costs you nothing to support JQOM.
     *
     * @return array A list of query languages supported by this transport.
     *
     * @see QueryManagerInterface::getSupportedQueryLanguages
     */
    public function getSupportedQueryLanguages();

    //TODO: method for stored queries?
}
