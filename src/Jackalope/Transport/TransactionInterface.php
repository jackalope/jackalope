<?php

namespace Jackalope\Transport;

/**
 * Defines the methods needed for Transaction support.
 *
 * @see <a href="http://www.day.com/specs/jcr/2.0/21_Transactions.html">JCR 2.0, chapter 21</a>
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 */
interface TransactionInterface extends TransportInterface
{

    /**
     * Initiates a "local transaction" on the root node
     *
     * @return string The received transaction token
     *
     * @throws \PHPCR\RepositoryException If no transaction token received.
     */
    beginTransaction();

    /**
     * Commits a transaction started with {@link beginTransaction()}
     */
    commitTransaction();

    /**
     * Rolls back a transaction started with {@link beginTransaction()}
     */
    rollbackTransaction();

    /**
     * Sets the default transaction timeout
     *
     * @param int $seconds The value of the timeout in seconds
     */
    setTransactionTimeout($seconds);
}
