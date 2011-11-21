<?php

namespace Jackalope\Transport;

/**
 * Implementation specific interface for implementing transactional transport
 * layers.
 *
 * Jackalope encapsulates all communication with the storage backend within
 * this interface.
 *
 * Adds the methods necessary for transaction handling
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 */
interface TransactionalInterface extends TransportInterface
{

    /**
     * Initiates a "local transaction" on the root node
     *
     * @return string The received transaction token
     *
     * @throws \PHPCR\RepositoryException If no transaction token received.
     */
    function beginTransaction();

    /**
     * Commits a transaction started with {@link beginTransaction()}
     */
    function commitTransaction();

    /**
     * Rolls back a transaction started with {@link beginTransaction()}
     */
    function rollbackTransaction();

    /**
     * Sets the default transaction timeout
     *
     * @param int $seconds The value of the timeout in seconds
     */
    function setTransactionTimeout($seconds);
}
