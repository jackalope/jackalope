<?php

namespace Jackalope;

use ArrayIterator;

/**
 * Namespace registry for Jackalope
 *
 * {@inheritDoc}
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
    protected $userNamespaces = null;

    /**
     * Initializes the created object.
     *
     * @param object $factory  an object factory implementing "get" as described in \Jackalope\Factory
     * @param TransportInterface $transport
     *
     * @see \Jackalope\Factory
     */
    public function __construct($factory, TransportInterface $transport)
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
     *
     * @return void
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

    // inherit all doc
    /**
     * @api
     */
    public function registerNamespace($prefix, $uri)
    {
        // prevent default namespace prefixes to be overridden.
        $this->checkPrefix($prefix);

        // prevent default namespace uris to be overridden
        if (false !== array_search($uri, $this->defaultNamespaces)) {
            throw new \PHPCR\NamespaceException("Can not change default namespace $prefix = $uri");
        }
        $this->lazyLoadNamespaces();
        //first try putting the stuff in backend, and only afterwards update lokal info

        // this has no impact on running sessions, go directly to storage
        $this->transport->registerNamespace($prefix, $uri);

        // update local info
        if (false !== $oldpref = array_search($uri, $this->userNamespaces)) {
            // the backend takes care of storing this, but we have to update frontend info
            unset($this->userNamespaces[$oldpref]);
        }
        $this->userNamespaces[$prefix] = $uri;
    }

    // inherit all doc
    /**
     * @api
     */
    public function unregisterNamespace($prefix)
    {
        $this->lazyLoadNamespaces();
        $this->checkPrefix($prefix);
        if (! array_key_exists($prefix, $this->userNamespaces)) {
            // we already checked whether this is a prefix out of the defaultNamespaces in checkPrefix
            throw new \PHPCR\NamespaceException("Prefix $prefix is not currently registered");
        }
        $this->transport->unregisterNamespace($prefix);
    }

    // inherit all doc
    /**
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

    // inherit all doc
    /**
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

    // inherit all doc
    /**
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
        throw new \PHPCR\NamespaceException("Mapping for '$prefix' is not defined");
    }

    // inherit all doc
    /**
     * @api
     */
    public function getPrefix($uri)
    {
        $prefix = array_search($uri, $this->defaultNamespaces);
        if ($prefix === false) {
            $this->lazyLoadNamespaces();
            $prefix = array_search($uri, $this->userNamespaces);
            if ($prefix === false) {
                throw new \PHPCR\NamespaceException("URI '$uri' is not defined in registry");
            }
        }
        return $prefix;
    }

    /**
     * Provide Traversable interface: iterator over all namespaces
     *
     * @return \Iterator over all namespaces, with prefix as key and url as value
     */
    public function getIterator()
    {
        $this->lazyLoadNamespaces();
        return new ArrayIterator(array_merge($this->defaultNamespaces, $this->userNamespaces));
    }

    /**
     * Implement verification if this is a valid prefix
     *
     * Throws the \PHPCR\NamespaceException if trying to use one of the
     * built-in prefixes or a prefix that begins with the characters "xml"
     * (in any combination of case)
     *
     * @return void
     *
     * @throws \PHPCR\NamespaceException if re-assign built-in prefix or prefix starting with xml
     *
     * @private
     * TODO: can we refactor Session::setNamespacePrefix to not need to directly access this?
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
