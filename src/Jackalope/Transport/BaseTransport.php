<?php

namespace Jackalope\Transport;

use PHPCR\RepositoryException;
use PHPCR\Util\PathHelper;

/**
 * Base class for transport implementation.
 *
 * Collects useful methods that are independent of backend implementations
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 * @author David Buchmann <david@liip.ch>
 */
abstract class BaseTransport implements TransportInterface
{
    /**
     * @see TransportInterface::setFetchDepth($depth)
     */
    protected int $fetchDepth = 0;

    /**
     * Flag to determine if mix:lastModified nodes should be updated
     * automatically.
     */
    private bool $autoLastModified = true;

    /**
     * Minimal check according to the jcr spec to see if this node name
     * conforms to the specification.
     *
     * If it can't be avoided, extending transports may overwrite this method to add
     * additional checks. But this will reduce interchangeability, thus it is better to
     * properly encode and decode characters that are not natively allowed by the storage.
     *
     * @return bool always true, if the name is not valid a RepositoryException is thrown
     *
     * @throws RepositoryException if the name contains invalid characters
     *
     *@see http://www.day.com/specs/jcr/2.0/3_Repository_Model.html#3.2.2%20Local%20Names
     */
    public function assertValidName($name): bool
    {
        return PathHelper::assertValidLocalName($name);
    }

    public function setFetchDepth(int $depth): void
    {
        $this->fetchDepth = $depth;
    }

    public function getFetchDepth(): int
    {
        return $this->fetchDepth;
    }

    public function setAutoLastModified(bool $autoLastModified): void
    {
        $this->autoLastModified = $autoLastModified;
    }

    public function getAutoLastModified(): bool
    {
        return $this->autoLastModified;
    }

    // TODO: #46 add method to generate capabilities from implemented interfaces
}
