<?php
namespace Jackalope;

/**
 * Mirrors namespaces with jackarabbit backend
 */
class NamespaceRegistry implements \IteratorAggregate, \PHPCR\NamespaceRegistryInterface
{
    /**
     * Instance of an implementation of the TransportInterface
     * @var TransportInterface
     */
    protected $transport;

    /**
     * The factory to instantiate objects
     * @var Factory
     */
    protected $factory;

    /**
     * List of predefined namespaces.
     * @var array
     */
    protected $defaultNamespaces = array(
        self::PREFIX_JCR   => self::NAMESPACE_JCR,
        self::PREFIX_NT    => self::NAMESPACE_NT,
        self::PREFIX_MIX   => self::NAMESPACE_MIX,
        self::PREFIX_XML   => self::NAMESPACE_XML,
        self::PREFIX_EMPTY => self::NAMESPACE_EMPTY
    );

    /**
     * Set of namespaces registered by the user.
     * @var array
     */
    protected $userNamespaces = array();

    /**
     * Initializes the created object.
     *
     * @param object $factory  an object factory implementing "get" as described in \Jackalope\Factory
     * @param TransportInterface $transport
     */
    public function __construct($factory, TransportInterface $transport)
    {
        $this->factory = $factory;
        $this->transport = $transport;

        $namespaces = $transport->getNamespaces();
        foreach ($namespaces as $prefix => $uri) {
            if (! array_key_exists($prefix, $this->defaultNamespaces)) {
                $this->userNamespaces[$prefix] = $uri;
            }
        }
    }

    /**
     * Sets a one-to-one mapping between prefix and uri in the global namespace
     * registry of this repository.
     * Assigning a new prefix to a URI that already exists in the namespace
     * registry erases the old prefix. In general this can almost always be
     * done, though an implementation is free to prevent particular
     * remappings by throwing a NamespaceException.
     *
     * On the other hand, taking a prefix that is already assigned to a URI
     * and re-assigning it to a new URI in effect unregisters that URI.
     * Therefore, the same restrictions apply to this operation as to
     * NamespaceRegistry.unregisterNamespace:
     * - Attempting to re-assign a built-in prefix (jcr, nt, mix, sv, xml,
     *   or the empty prefix) to a new URI will throw a
     *   \PHPCR\NamespaceException.
     * - Attempting to register a namespace with a prefix that begins with
     *   the characters "xml" (in any combination of case) will throw a
     *   \PHPCR\NamespaceException.
     * - An implementation may prevent the re-assignment of any other namespace
     *   prefixes for implementation-specific reasons by throwing a
     *   \PHPCR\NamespaceException.
     *
     * @param string $prefix The prefix to be mapped.
     * @param string $uri The URI to be mapped.
     *
     * @return void
     *
     * @throws \PHPCR\NamespaceException If an attempt is made to re-assign a built-in prefix to a new URI or, to register a namespace with a prefix that begins with the characters "xml" (in any combination of case) or an attempt is made to perform a prefix re-assignment that is forbidden for implementation-specific reasons.
     * @throws \PHPCR\UnsupportedRepositoryOperationException if this repository does not support namespace registry changes.
     * @throws \PHPCR\AccessDeniedException if the current session does not have sufficient access to register the namespace.
     * @throws \PHPCR\RepositoryException if another error occurs.
     */
    public function registerNamespace($prefix, $uri)
    {
        // prevent default namespace prefixes to be overridden.
        $this->checkPrefix($prefix);

        // prevent default namespace uris to be overridden
        if (false !== array_search($uri, $this->defaultNamespaces)) {
            throw new \PHPCR\NamespaceException("Can not change default namespace $prefix = $uri");
        }

        // check if prefix is already mapped
        if (isset($this->userNamespaces[$prefix])) {
            if ($this->userNamespaces[$prefix] == $uri) {
                // nothing to do, we already have the mapping
                return;
            }
            // unregister old mapping
            $this->unregisterNamespace($prefix);
        }
        // check if target uri already exists and unregister if so
        if (false !== $prefix = array_search($uri, $this->userNamespaces)) {
            $this->unregisterNamespace($prefix);
        }

        //first try putting the stuff in backend, and only afterwards update lokal info

        // this has no impact on running sessions, go directly to storage
        $this->transport->registerNamespace($prefix, $uri);

        // update local info
        $this->userNamespaces[$prefix] = $uri;
    }

    /**
     * Removes a namespace mapping from the registry. The following restriction
     * apply:
     * * Attempting to unregister a built-in namespace (jcr, nt, mix, sv, xml or
     *   the empty namespace) will throw a \PHPCR\NamespaceException.
     * * An attempt to unregister a namespace that is not currently registered
     *   will throw a \PHPCR\NamespaceException.
     * * An implementation may prevent the unregistering of any other namespace
     *   for implementation-specific reasons by throwing a
     *   \PHPCR\NamespaceException.
     *
     * @param string $prefix The prefix of the mapping to be removed.
     * @return void
     * @throws \PHPCR\NamespaceException unregister a built-in namespace or a namespace that is not currently registered or a namespace whose unregsitration is forbidden for implementation-specific reasons.
     * @throws \PHPCR\UnsupportedRepositoryOperationException if this repository does not support namespace registry changes.
     * @throws \PHPCR\AccessDeniedException if the current session does not have sufficient access to unregister the namespace.
     * @throws \PHPCR\RepositoryException if another error occurs.
     */
    public function unregisterNamespace($prefix)
    {
        $this->checkPrefix($prefix);
        if (! array_key_exists($prefix, $this->userNamespaces)) {
            //defaultNamespaces would throw an exception in checkPrefix already
            throw new \PHPCR\NamespaceException("Prefix $prefix is not currently registered");
        }
        $this->transport->unregisterNamespace($prefix);
    }

    /**
     * Returns an array holding all currently registered prefixes.
     *
     * @return array a string array
     */
    public function getPrefixes()
    {
        return array_merge(
            array_keys($this->defaultNamespaces),
            array_keys($this->userNamespaces)
        );
    }

    /**
     * Returns an array holding all currently registered URIs.
     *
     * @return array a string array
     * @throws \PHPCR\RepositoryException if an error occurs.
     * @api
     */
    public function getURIs()
    {
        return array_merge(
            array_values($this->defaultNamespaces),
            array_values($this->userNamespaces)
        );
    }

    /**
     * Returns the URI to which the given prefix is mapped.
     *
     * @param string $prefix a string
     * @return string a string
     *
     * @throws \PHPCR\NamespaceException if a mapping with the specified prefix does not exist.
     */
    public function getURI($prefix)
    {
        if (isset($this->defaultNamespaces[$prefix])) {
            return $this->defaultNamespaces[$prefix];
        } elseif (isset($this->userNamespaces[$prefix])) {
            return $this->userNamespaces[$prefix];
        }
        throw new \PHPCR\NamespaceException("Mapping for '$prefix' is not defined");
    }

    /**
     * Returns the prefix which is mapped to the given uri.
     *
     * @param string $uri a string
     * @return string a string
     *
     * @throws \PHPCR\NamespaceException if a mapping with the specified uri does not exist.
     * @throws \PHPCR\RepositoryException if another error occurs
     */
    public function getPrefix($uri)
    {
        $prefix = array_search($uri, $this->defaultNamespaces);
        if ($prefix === false) {
            $prefix = array_search($uri, $this->userNamespaces);
            if ($prefix === false) {
                throw new \PHPCR\NamespaceException("URI '$uri' is not defined in registry");
            }
        }
        return $prefix;
    }

    /**
     * Exposes the set of default namespaces.
     *
     * @return array
     */
    public function getDefaultNamespaces()
    {
        return $this->defaultNamespaces;
    }

    /**
     * Provide Traversable interface: iterator over all namespaces
     *
     * @return Iterator over all namespaces, with prefix as key and url as value
     */
    public function getIterator()
    {
        return new \ArrayIterator(array_merge($this->defaultNamespaces, $this->userNamespaces));
    }

    /**
     * Verifies whether this is a valid prefix
     *
     *
     * Throws the \PHPCR\NamespaceException if trying to use one of the
     * built-in prefixes or a prefix that begins with the characters "xml"
     * (in any combination of case)
     *
     *
     * @return void
     *
     * @throws \PHPCR\NamespaceException if re-assign built-in prefix or prefix starting with xml
     */
    public function checkPrefix($prefix)
    {
        if (! strncasecmp('xml', $prefix, 3)) {
            throw new \PHPCR\NamespaceException("Do not use xml in prefixes for namespace changes: '$prefix'");
        }
        if (array_key_exists($prefix, $this->defaultNamespaces)) {
            throw new \PHPCR\NamespaceException("Do not change the predefined prefixes: '$prefix'");
        }
        if (false !== strpos($prefix, ' ') || false !== strpos($prefix, ':')) {
            throw new \PHPCR\NamespaceException("Not a valid namespace prefix '$prefix'");
        }
    }
}
