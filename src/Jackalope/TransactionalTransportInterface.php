<?php
/**
 * Definition of the interface to be used for implementing a transactional transport.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 *
 * @package jackalope
 * @subpackage transport
 */
namespace Jackalope;

/**
 * Implementation specific interface:
 * Jackalope encapsulates all communication with the storage backend within
 * this interface.
 *
 * Adds the methods necessary for transaction handling
 *
 * @package jackalope
 * @subpackage transport
 */
interface TransactionalTransportInterface extends TransportInterface
{

    /**
     * Initiates a Çlocal transactionÈ on the root node
     *
     * @return string The received transaction token
     * @throws \PHPCR\RepositoryException If no transaction token received
     */
    function beginTransaction();

    /**
     * Commits a transaction started with {@link beginTransaction()}
     */
    function commitTransaction();

    /**
     * Rollbacks a transaction started with {@link beginTransaction()}
     */
    function rollbackTransaction();

    /**
     * Sets the default transaction timeout
     *
     * @param int $seconds The value of the timeout in seconds
     */
    function setTransactionTimeout($seconds);
}
