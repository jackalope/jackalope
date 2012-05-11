<?php

namespace Jackalope\Transport;

use PHPCR\RepositoryException;

/**
 * Base class for transport implementation.
 *
 * Collects useful methods that are independant of backend implementations
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache License Version 2.0, January 2004
 *
 * @author David Buchmann <david@liip.ch>
 */

abstract class BaseTransport implements TransportInterface
{
    const VALIDATE_URI_RFC3986 = "
/^
([a-z][a-z0-9\*\-\.]*):\/\/
(?:
  (?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*
  (?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@
)?
(?:
  (?:[a-z0-9\-\.]|%[0-9a-f]{2})+
  |(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\])
)
(?::[0-9]+)?
(?:[\/|\?]
  (?:[\w#!:\.\?\+=&@!$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})
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
     * Helper method to check whether the path conforms to the specification
     * and is supported by this implementation
     *
     * Note that the rest of jackalope might not properly check paths in
     * getNode requests and similar so your transport should call this whenever
     * it needs to look up something in the storage to give a good error
     * message and not just not found.
     *
     * TODO: the spec is extremly open and recommends to restrict further.
     * TODO: how should this interact with assertValidName? The name may not contain / : or [] but the path of course can
     *
     * Paths have to be normalized before being checked, i.e. /node/./ is / and /my/node/.. is /my
     *
     * @param string $path The path to validate
     * @param bool $destination is the $path a destination path (by copy or move)?
     *
     * @return bool always true, if the name is not valid a RepositoryException is thrown
     *
     * @throws RepositoryException if the path contains invalid characters
     */
    public function assertValidPath($path, $destination = false)
    {
        if ('/' != substr($path, 0, 1)) {
            //sanity check
            throw new RepositoryException("Implementation error: '$path' is not an absolute path");
        }
        if ('/' != $path[0]
            || strpos($path, '//') !== false
            || strpos($path, '/./') !== false
            || strpos($path, '/../') !== false
        ) {
            throw new RepositoryException('Path is not well-formed or contains invalid characters: ' . $path);
        }

        if ($destination) {
            if (']' == substr($path, -1, 1)) {
                // TODO: Understand assumptions of CopyMethodsTest::testCopyInvalidDstPath more
                throw new RepositoryException('Invalid destination path');
            }
        }

        return true;
    }

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
     * @return always true, if the name is not valid a RepositoryException is thrown
     *
     * @see http://www.day.com/specs/jcr/2.0/3_Repository_Model.html#3.2.2%20Local%20Names
     *
     * @throws RepositoryException if the name contains invalid characters
     */
    public function assertValidName($name)
    {
        if ('.' == $name || '..' == $name) {
            throw new RepositoryException('Node name may not be parent or self identifier: ' . $name);
        }

        if (preg_match('/\/|:|\[|\]|\||\*/', $name)) {
            throw new RepositoryException('Node name contains illegal characters: '.$name);
        }

        return true;
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

    // TODO: #46 add method to generate capabilities from implemented interfaces
}
