<?php

namespace Jackalope\Transport;

use Jackalope\Query\Query;

/**
 * Defines the methods needed for Query support
 *
 * @see <a href="http://www.day.com/specs/jcr/2.0/6_Query.html">JCR 2.0, chapter 6</a>
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
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
    function query(Query $query);

    //TODO: getSupportedQueryLanguages

    //TODO: method for stored queries?
}
