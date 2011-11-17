<?php

namespace Jackalope;

use Jackalope\NodeType\NodeTypeManager;

/**
 * Implementation specific interface for implementing transactional transport
 * layers.
 *
 * Jackalope encapsulates all communication with the storage backend within
 * this interface.
 *
 * Adds the methods necessary for query handling
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 */
interface QueryableTransportInterface extends TransportInterface
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
     * @param \PHPCR\Query\QueryInterface $query the query object
     *
     * @return array data with search result. TODO: what to return? should be some simple array
     *
     * @see Query\QueryResult::__construct for the xml format. TODO: have the transport return a QueryResult?
     */
    public function query(\PHPCR\Query\QueryInterface $query);

    //TODO: getSupportedQueryLanguages
}
