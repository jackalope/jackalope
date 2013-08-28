<?php

namespace Jackalope\Query;

use PHPCR\UnsupportedRepositoryOperationException;
use PHPCR\RepositoryException;
use PHPCR\ItemNotFoundException;
use PHPCR\Query\QueryInterface;

use Jackalope\ObjectManager;
use Jackalope\FactoryInterface;

/**
 * Abstract Query implementation for the different Query-languages
 *
 * This can never be legally created if the transport does not implement
 * QueryInterface.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
abstract class Query implements QueryInterface
{
    /**
     * The factory to instantiate objects
     * @var FactoryInterface
     */
    protected $factory;
    /**
     * The query statement
     * @var string
     */
    protected $statement;
    /**
     * Limit for the query
     * @var integer
     */
    protected $limit;
    /**
     * Offset to start results from
     * @var integer
     */
    protected $offset;
    /**
     * The object manager to execute the query with.
     *
     * @var \Jackalope\ObjectManager
     */
    protected $objectManager;
    /**
     * If this is a stored query, the path to the node that stores this query.
     * @var string
     */
    protected $path;

    /**
     * Create a new query instance
     *
     * @param FactoryInterface $factory       the object factory
     * @param string           $statement     The statement for this query
     * @param ObjectManager    $objectManager (can be omitted if you do not want
     *      to execute the query but just use it with a parser)
     * @param string $path If this query is loaded from workspace with
     *      QueryManager::getQuery(), path has to be provided here
     */
    public function __construct(FactoryInterface $factory, $statement, ObjectManager $objectManager = null, $path = null)
    {
        $this->factory = $factory;
        $this->statement = $statement;
        $this->objectManager = $objectManager;
        $this->path = $path;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function bindValue($varName, $value)
    {
        throw new RepositoryException('Not Implemented...');
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function execute()
    {
        if (is_null($this->objectManager)) {
            // if the ObjectManager was not injected in the header. this is only supposed to happen in the DBAL client.
            throw new RepositoryException('Jackalope implementation error: This query was built for parsing only. (There is no ObjectManager to run the query against.)');
        }
        $transport = $this->objectManager->getTransport();
        $rawData = $transport->query($this);
        $queryResult = $this->factory->get(
            'Query\QueryResult',
            array(
                $rawData,
                $this->objectManager,
            )
        );

        return $queryResult;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function cancel()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getBindVariableNames()
    {
        throw new RepositoryException('Not Implemented...');
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * Access the limit from the transport layer
     *
     * @return int the limit set with setLimit
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
    }

    /**
     * Access the offset from the transport layer
     *
     * @return int the offset set with setOffset
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getStatement()
    {
        return $this->statement;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getStoredQueryPath()
    {
        if ($this->path == null) {
            throw new ItemNotFoundException('Not a stored query');
        }

        return $this->path;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function storeAsNode($absPath)
    {
        // when implementing this, use ->getStatement***() and not $this->statement
        // so this works for the extending QueryObjectModel as well
        throw new UnsupportedRepositoryOperationException('Not implemented: Write');
    }

}
