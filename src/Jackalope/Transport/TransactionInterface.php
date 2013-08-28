<?php

namespace Jackalope\Transport;

/**
 * Defines the methods needed for Transaction support.
 *
 * @see <a href="http://www.day.com/specs/jcr/2.0/21_Transactions.html">JCR 2.0, chapter 21</a>
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
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
    public function beginTransaction();

    /**
     * Commits a transaction started with {@link beginTransaction()}
     */
    public function commitTransaction();

    /**
     * Rolls back a transaction started with {@link beginTransaction()}
     */
    public function rollbackTransaction();

    /**
     * Sets the default transaction timeout
     *
     * @param int $seconds The value of the timeout in seconds
     */
    public function setTransactionTimeout($seconds);
}
