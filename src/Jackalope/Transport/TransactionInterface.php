<?php

namespace Jackalope\Transport;

use PHPCR\RepositoryException;

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
     * Initiates a "local transaction" on the root node.
     *
     * @return string A transaction token that can be used for debugging
     *
     * @throws RepositoryException if no transaction token received
     */
    public function beginTransaction(): ?string;

    /**
     * Commits a transaction started with {@link beginTransaction()}.
     */
    public function commitTransaction(): void;

    /**
     * Rolls back a transaction started with {@link beginTransaction()}.
     */
    public function rollbackTransaction(): void;

    /**
     * Sets the default transaction timeout.
     */
    public function setTransactionTimeout(int $seconds): void;
}
