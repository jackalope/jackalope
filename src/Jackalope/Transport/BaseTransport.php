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
 *
 * @author David Buchmann <david@liip.ch>
 */

abstract class BaseTransport implements TransportInterface
{
    const VALIDATE_URI_RFC3986 = "
/^
([a-z][a-z0-9\\*\\-\\.]*):\\/\\/
(?:
  (?:(?:[\\w\\.\\-\\+!$&'\\(\\)*\\+,;=]|%[0-9a-f]{2})+:)*
  (?:[\\w\\.\\-\\+%!$&'\\(\\)*\\+,;=]|%[0-9a-f]{2})+@
)?
(?:
  (?:[a-z0-9\\-\\.]|%[0-9a-f]{2})+
  |(?:\\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\\])
)
(?::[0-9]+)?
(?:[\\/|\\?]
  (?:[\\w#!:\\.\\?\\+=&@!$'~*,;\\/\\(\\)\\[\\]\\-]|%[0-9a-f]{2})
*)?
$/xi";

    /**
    * The current fetchDepth
    *
    * @var int
    *
    * @see TransportInterface::setFetchDepth($depth)
    */
    protected $fetchDepth = 0;

    /**
     * Flag to determine if mix:lastModified nodes should be updated
     * automatically.
     *
     * @var boolean
     */
    private $autoLastModified = true;

    /**
     * Minimal check according to the jcr spec to see if this node name
     * conforms to the specification
     *
     * If it can't be avoided, extending transports may overwrite this method to add
     * additional checks. But this will reduce interchangeability, thus it is better to
     * properly encode and decode characters that are not natively allowed by the storage.
     *
     * @param string $name The name to check
     *
     * @return boolean always true, if the name is not valid a RepositoryException is thrown
     *
     * @see http://www.day.com/specs/jcr/2.0/3_Repository_Model.html#3.2.2%20Local%20Names
     *
     * @throws RepositoryException if the name contains invalid characters
     */
    public function assertValidName($name)
    {
        return PathHelper::assertValidLocalName($name);
    }

    /**
     * {@inheritDoc}
     */
    public function setFetchDepth($depth)
    {
        $this->fetchDepth = $depth;
    }

    /**
     * {@inheritDoc}
     */
    public function getFetchDepth()
    {
        return $this->fetchDepth;
    }

    /**
     * {@inheritDoc}
     */
    public function setAutoLastModified($autoLastModified)
    {
        $this->autoLastModified = $autoLastModified;
    }

    /**
     * {@inheritDoc}
     */
    public function getAutoLastModified()
    {
        return $this->autoLastModified;
    }

    // TODO: #46 add method to generate capabilities from implemented interfaces
}
