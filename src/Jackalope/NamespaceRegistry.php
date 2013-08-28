<?php

namespace Jackalope;

use Iterator;
use ArrayIterator;
use IteratorAggregate;

use PHPCR\UnsupportedRepositoryOperationException;
use PHPCR\ItemNotFoundException;
use PHPCR\NamespaceRegistryInterface;
use PHPCR\NamespaceException;

use Jackalope\Transport\TransportInterface;
use Jackalope\Transport\WritingInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
class NamespaceRegistry implements IteratorAggregate, NamespaceRegistryInterface
{
    /**
     * Instance of an implementation of the TransportInterface
     * @var WritingInterface
     */
    protected $transport;

    /**
     * The factory to instantiate objects
     * @var FactoryInterface
     */
    protected $factory;

    /**
     * List of predefined namespaces.
     * @var array
     */
    protected $defaultNamespaces = array(
        self::PREFIX_JCR   => self::NAMESPACE_JCR,
        self::PREFIX_SV    => self::NAMESPACE_SV,
        self::PREFIX_NT    => self::NAMESPACE_NT,
        self::PREFIX_MIX   => self::NAMESPACE_MIX,
        self::PREFIX_XML   => self::NAMESPACE_XML,
        self::PREFIX_EMPTY => self::NAMESPACE_EMPTY,
    );

    /**
     * Set of namespaces registered by the user.
     * @var array
     */
    protected $userNamespaces = null;

    /**
     * Initializes the created object.
     *
     * @param FactoryInterface   $factory
     * @param TransportInterface $transport
     *
     * @throws ItemNotFoundException If property not found
     */
    public function __construct(FactoryInterface $factory, TransportInterface $transport)
    {
        $this->factory = $factory;
        $this->transport = $transport;
    }

    /**
     * Makes sure the namespaces are available.
     *
     * We are lazy and only load the namespaces when they are needed for the
     * first time. This method has to be called by all methods that need to
     * do something with user defined namespaces.
     */
    protected function lazyLoadNamespaces()
    {
        if ($this->userNamespaces === null) {
            $namespaces = $this->transport->getNamespaces();
            $this->userNamespaces = array();
            foreach ($namespaces as $prefix => $uri) {
                if (! array_key_exists($prefix, $this->defaultNamespaces)) {
                    $this->userNamespaces[$prefix] = $uri;
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function registerNamespace($prefix, $uri)
    {
        if (! $this->transport instanceof WritingInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support writing');
        }

        // prevent default namespace prefixes to be overridden.
        $this->checkPrefix($prefix);

        // prevent default namespace uris to be overridden
        if (false !== array_search($uri, $this->defaultNamespaces)) {
            throw new NamespaceException("Can not change default namespace $prefix = $uri");
        }
        $this->lazyLoadNamespaces();
        //first try putting the stuff in backend, and only afterwards update local info

        // this has no impact on running sessions, go directly to storage
        $this->transport->registerNamespace($prefix, $uri);

        // update local info
        if (false !== $oldpref = array_search($uri, $this->userNamespaces)) {
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
    public function unregisterNamespaceByURI($uri)
    {
        if (! $this->transport instanceof WritingInterface) {
            throw new UnsupportedRepositoryOperationException('Transport does not support writing');
        }

        $this->lazyLoadNamespaces();
        $prefix = array_search($uri, $this->userNamespaces);
        if ($prefix === false) {
            throw new NamespaceException("Namespace '$uri' is not currently registered");
        }
        // now check whether this is a prefix out of the defaultNamespaces in checkPrefix
        $this->checkPrefix($prefix);

        $this->transport->unregisterNamespace($prefix);
        //remove the prefix from the local userNamespaces array
        unset($this->userNamespaces[$prefix]);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getPrefixes()
    {
        $this->lazyLoadNamespaces();

        return array_merge(
            array_keys($this->defaultNamespaces),
            array_keys($this->userNamespaces)
        );
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getURIs()
    {
        $this->lazyLoadNamespaces();

        return array_merge(
            array_values($this->defaultNamespaces),
            array_values($this->userNamespaces)
        );
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getURI($prefix)
    {
        $this->lazyLoadNamespaces();
        if (isset($this->defaultNamespaces[$prefix])) {
            return $this->defaultNamespaces[$prefix];
        } elseif (isset($this->userNamespaces[$prefix])) {
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
    public function getPrefix($uri)
    {
        $prefix = array_search($uri, $this->defaultNamespaces);
        if ($prefix === false) {
            $this->lazyLoadNamespaces();
            $prefix = array_search($uri, $this->userNamespaces);
            if ($prefix === false) {
                throw new NamespaceException("URI '$uri' is not defined in registry");
            }
        }

        return $prefix;
    }

    /**
     * Provide Traversable interface: iterator over all namespaces
     *
     * @return Iterator over all namespaces, with prefix as key and url as value
     */
    public function getIterator()
    {
        $this->lazyLoadNamespaces();

        return new ArrayIterator(array_merge($this->defaultNamespaces, $this->userNamespaces));
    }

    /**
     * Implement verification if this is a valid prefix
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
    public function checkPrefix($prefix)
    {
        if (! strncasecmp('xml', $prefix, 3)) {
            throw new NamespaceException("Do not use xml in prefixes for namespace changes: '$prefix'");
        }
        if (array_key_exists($prefix, $this->defaultNamespaces)) {
            throw new NamespaceException("Do not change the predefined prefixes: '$prefix'");
        }
        if (false !== strpos($prefix, ' ') || false !== strpos($prefix, ':')) {
            throw new NamespaceException("Not a valid namespace prefix '$prefix'");
        }
    }

    /**
     * Get all defined namespaces
     *
     * @private
     */
    public function getNamespaces()
    {
        $this->lazyLoadNamespaces();

        return array_merge($this->defaultNamespaces, $this->userNamespaces);
    }
}
