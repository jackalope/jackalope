<?php

namespace Jackalope\Query;

use Jackalope\FactoryInterface;
use Jackalope\ObjectManager;
use PHPCR\ItemNotFoundException;
use PHPCR\NodeInterface;
use PHPCR\Query\QueryInterface;
use PHPCR\Query\QueryResultInterface;
use PHPCR\RepositoryException;
use PHPCR\UnsupportedRepositoryOperationException;

/**
 * Abstract Query implementation for the different Query-languages.
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
    protected FactoryInterface $factory;

    /**
     * The object manager to execute the query with.
     */
    private ?ObjectManager $objectManager;

    /**
     * The query statement.
     */
    private string $statement;

    /**
     * Limit for the query.
     */
    private ?int $limit = null;

    /**
     * Offset to start results from.
     */
    private ?int $offset = null;

    /**
     * If this is a stored query, the path to the node that stores this query.
     */
    private ?string $path;

    /**
     * Create a new query instance.
     *
     * @param FactoryInterface   $factory       the object factory
     * @param string             $statement     The statement for this query
     * @param ObjectManager|null $objectManager omit if you do not want to execute the query but just use it with a parser
     * @param string             $path          If this query is loaded from workspace with
     *                                          QueryManager::getQuery(), path has to be provided here
     */
    public function __construct(FactoryInterface $factory, $statement, ?ObjectManager $objectManager = null, $path = null)
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
    public function bindValue($varName, $value): void
    {
        throw new RepositoryException('Not Implemented...');
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function execute(): QueryResultInterface
    {
        if (null === $this->objectManager) {
            // if the ObjectManager was not injected in the header. this is only supposed to happen in the DBAL client.
            throw new RepositoryException('Jackalope implementation error: This query was built for parsing only. (There is no ObjectManager to run the query against.)');
        }
        $transport = $this->objectManager->getTransport();
        $rawData = $transport->query($this);

        return $this->factory->get(QueryResult::class, [$rawData, $this->objectManager]);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function cancel(): bool
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
    public function setLimit($limit): void
    {
        $this->limit = $limit;
    }

    /**
     * Access the limit from the transport layer.
     *
     * @return int|null the limit set with setLimit
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setOffset($offset): void
    {
        $this->offset = $offset;
    }

    /**
     * Access the offset from the transport layer.
     *
     * @return int|null the offset set with setOffset
     */
    public function getOffset(): ?int
    {
        return $this->offset;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getStatement(): string
    {
        return $this->statement;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getStoredQueryPath(): ?string
    {
        if (null === $this->path) {
            throw new ItemNotFoundException('Not a stored query');
        }

        return $this->path;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function storeAsNode($absPath): NodeInterface
    {
        // when implementing this, use ->getStatement***() and not $this->statement
        // so this works for the extending QueryObjectModel as well
        throw new UnsupportedRepositoryOperationException('Not implemented: Write');
    }
}
