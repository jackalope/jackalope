<?php

namespace Jackalope;

use Jackalope\Transport\TransportInterface;
use Jackalope\Transport\WritingInterface;
use PHPCR\NamespaceException;
use PHPCR\NamespaceRegistryInterface;
use PHPCR\UnsupportedRepositoryOperationException;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
final class NamespaceRegistry implements \IteratorAggregate, NamespaceRegistryInterface
{
    /**
     * Instance of an implementation of the TransportInterface.
     */
    private TransportInterface $transport;

    /**
     * List of predefined namespaces.
     */
    private const DEFAULT_NAMESPACES = [
        self::PREFIX_JCR => self::NAMESPACE_JCR,
        self::PREFIX_SV => self::NAMESPACE_SV,
        self::PREFIX_NT => self::NAMESPACE_NT,
        self::PREFIX_MIX => self::NAMESPACE_MIX,
        self::PREFIX_XML => self::NAMESPACE_XML,
        self::PREFIX_EMPTY => self::NAMESPACE_EMPTY,
    ];

    /**
     * Set of namespaces registered by the user.
     */
    private array $userNamespaces;

    public function __construct(FactoryInterface $factory, TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    /**
     * Makes sure the namespaces are available.
     *
     * We are lazy and only load the namespaces when they are needed for the
     * first time. This method has to be called by all methods that need to
     * do something with user defined namespaces.
     */
    private function lazyLoadNamespaces(): void
    {
        if (isset($this->userNamespaces)) {
            return;
        }
        $namespaces = $this->transport->getNamespaces();
        $this->userNamespaces = [];
        foreach ($namespaces as $prefix => $uri) {
            if (!array_key_exists($prefix, self::DEFAULT_NAMESPACES)) {
                $this->userNamespaces[$prefix] = $uri;
            }
        }
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function registerNamespace($prefix, $uri): void
    {
        if (!$this->transport instanceof WritingInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support writing');
        }

        // prevent default namespace prefixes to be overridden.
        $this->checkPrefix($prefix);

        // prevent default namespace uris to be overridden
        if (in_array($uri, self::DEFAULT_NAMESPACES, true)) {
            throw new NamespaceException("Can not change default namespace $prefix = $uri");
        }

        $this->lazyLoadNamespaces();
        // first try putting the stuff in backend, and only afterwards update local info

        // this has no impact on running sessions, go directly to storage
        $this->transport->registerNamespace($prefix, $uri);

        // update local info
        if (false !== $oldpref = array_search($uri, $this->userNamespaces, true)) {
            // the backend takes care of storing this, but we have to update frontend info
            unset($this->userNamespaces[$oldpref]);
        }

        $this->userNamespaces[$prefix] = $uri;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function unregisterNamespaceByURI($uri): void
    {
        if (!$this->transport instanceof WritingInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support writing');
        }

        $this->lazyLoadNamespaces();
        $prefix = array_search($uri, $this->userNamespaces, true);
        if (false === $prefix) {
            throw new NamespaceException("Namespace '$uri' is not currently registered");
        }
        // now check whether this is a prefix out of the defaultNamespaces in checkPrefix
        $this->checkPrefix($prefix);

        $this->transport->unregisterNamespace($prefix);
        // remove the prefix from the local userNamespaces array
        unset($this->userNamespaces[$prefix]);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getPrefixes(): array
    {
        $this->lazyLoadNamespaces();

        return array_merge(
            array_keys(self::DEFAULT_NAMESPACES),
            array_keys($this->userNamespaces)
        );
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getURIs(): array
    {
        $this->lazyLoadNamespaces();

        return array_merge(
            array_values(self::DEFAULT_NAMESPACES),
            array_values($this->userNamespaces)
        );
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getURI($prefix): string
    {
        $this->lazyLoadNamespaces();
        if (isset(self::DEFAULT_NAMESPACES[$prefix])) {
            return self::DEFAULT_NAMESPACES[$prefix];
        }
        if (isset($this->userNamespaces[$prefix])) {
            $this->lazyLoadNamespaces();

            return $this->userNamespaces[$prefix];
        }
        throw new NamespaceException("Mapping for '$prefix' is not defined");
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getPrefix($uri): string
    {
        $prefix = array_search($uri, self::DEFAULT_NAMESPACES, true);
        if (false === $prefix) {
            $this->lazyLoadNamespaces();
            $prefix = array_search($uri, $this->userNamespaces, true);
            if (false === $prefix) {
                throw new NamespaceException("URI '$uri' is not defined in registry");
            }
        }

        return $prefix;
    }

    /**
     * Provide Traversable interface: iterator over all namespaces.
     *
     * @return \Iterator over all namespaces, with prefix as key and url as value
     */
    public function getIterator(): \Iterator
    {
        $this->lazyLoadNamespaces();

        return new \ArrayIterator(array_merge(self::DEFAULT_NAMESPACES, $this->userNamespaces));
    }

    /**
     * Implement verification if this is a valid prefix.
     *
     * Throws the NamespaceException if trying to use one of the
     * built-in prefixes or a prefix that begins with the characters "xml"
     * (in any combination of case)
     *
     * @param string $prefix the prefix name to check
     *
     * @throws NamespaceException if re-assign built-in prefix or prefix starting with xml
     *
     * @private
     * TODO: can we refactor Session::setNamespacePrefix to not need to directly access this?
     */
    public function checkPrefix($prefix): void
    {
        if (!strncasecmp('xml', $prefix, 3)) {
            throw new NamespaceException("Do not use xml in prefixes for namespace changes: '$prefix'");
        }

        if (array_key_exists($prefix, self::DEFAULT_NAMESPACES)) {
            throw new NamespaceException("Do not change the predefined prefixes: '$prefix'");
        }

        if (false !== strpos($prefix, ' ') || false !== strpos($prefix, ':')) {
            throw new NamespaceException("Not a valid namespace prefix '$prefix'");
        }
    }

    /**
     * Get all defined namespaces.
     *
     * @private
     */
    public function getNamespaces(): array
    {
        $this->lazyLoadNamespaces();

        return array_merge(self::DEFAULT_NAMESPACES, $this->userNamespaces);
    }
}
