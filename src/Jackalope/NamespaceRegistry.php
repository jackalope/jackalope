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
     * Instance of the NamespaceManager
     *
     * @var NamespaceManager
     */
    protected $namespaceManager = null;

    /**
     * Initializes the created object.
     *
     * @param object $factory  an object factory implementing "get" as described in \jackalope\Factory
     * @param TransportInterface $transport
     */
    public function __construct($factory, TransportInterface $transport)
    {
        $this->factory = $factory;
        $this->transport = $transport;
        $this->namespaceManager = $this->factory->get('NamespaceManager', array($this->defaultNamespaces));

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
     * @return void
     *
     * @throws \PHPCR\NamespaceException If an attempt is made to re-assign a built-in prefix to a new URI or, to register a namespace with a prefix that begins with the characters "xml" (in any combination of case) or an attempt is made to perform a prefix re-assignment that is forbidden for implementation-specific reasons.
     * @throws \PHPCR\UnsupportedRepositoryOperationException if this repository does not support namespace registry changes.
     * @throws \PHPCR\AccessDeniedException if the current session does not have sufficient access to register the namespace.
     * @throws \PHPCR\RepositoryException if another error occurs.
     */
    public function registerNamespace($prefix, $uri)
    {
        throw new NotImplementedException('Write');

        // prevent default namespace to be overridden.
        $this->namespaceManager->checkPrefix($prefix);

        // update local info
        $this->userNamespaces[$prefix] = $uri;

        //first try putting the stuff in backend, and only afterwards update lokal info
        //validation happens on the server, see second request trace below
        /*
PROPPATCH /server/tests HTTP/1.1
Authorization: Basic YWRtaW46YWRtaW4=
User-Agent: Jakarta Commons-HttpClient/3.0
Host: localhost:8080
Content-Length: 1866
Content-Type: text/xml; charset=UTF-8

<?xml version="1.0" encoding="UTF-8"?><D:propertyupdate xmlns:D="DAV:"><D:set><D:prop><dcr:namespaces xmlns:dcr="http://www.day.com/jcr/webdav/1.0"><dcr:namespace><dcr:prefix/><dcr:uri/></dcr:namespace><dcr:namespace><dcr:prefix>nt</dcr:prefix><dcr:uri>http://www.jcp.org/jcr/nt/1.0</dcr:uri></dcr:namespace><dcr:namespace><dcr:prefix>test</dcr:prefix><dcr:uri>http://liip.to/jackalope</dcr:uri></dcr:namespace><dcr:namespace><dcr:prefix>xs</dcr:prefix><dcr:uri>http://www.w3.org/2001/XMLSchema</dcr:uri></dcr:namespace><dcr:namespace><dcr:prefix>xml</dcr:prefix><dcr:uri>http://www.w3.org/XML/1998/namespace</dcr:uri></dcr:namespace><dcr:namespace><dcr:prefix>crx</dcr:prefix><dcr:uri>http://www.day.com/crx/1.0</dcr:uri></dcr:namespace><dcr:namespace><dcr:prefix>fn_old</dcr:prefix><dcr:uri>http://www.w3.org/2004/10/xpath-functions</dcr:uri></dcr:namespace><dcr:namespace><dcr:prefix>mix</dcr:prefix><dcr:uri>http://www.jcp.org/jcr/mix/1.0</dcr:uri></dcr:namespace><dcr:namespace><dcr:prefix>lx</dcr:prefix><dcr:uri>http://flux-cms.org/2.0</dcr:uri></dcr:namespace><dcr:namespace><dcr:prefix>jcr</dcr:prefix><dcr:uri>http://www.jcp.org/jcr/1.0</dcr:uri></dcr:namespace><dcr:namespace><dcr:prefix>sling</dcr:prefix><dcr:uri>http://sling.apache.org/jcr/sling/1.0</dcr:uri></dcr:namespace><dcr:namespace><dcr:prefix>new_prefix</dcr:prefix><dcr:uri>http://a_new_namespace</dcr:uri></dcr:namespace><dcr:namespace><dcr:prefix>vlt</dcr:prefix><dcr:uri>http://www.day.com/jcr/vault/1.0</dcr:uri></dcr:namespace><dcr:namespace><dcr:prefix>sv</dcr:prefix><dcr:uri>http://www.jcp.org/jcr/sv/1.0</dcr:uri></dcr:namespace><dcr:namespace><dcr:prefix>fn</dcr:prefix><dcr:uri>http://www.w3.org/2005/xpath-functions</dcr:uri></dcr:namespace><dcr:namespace><dcr:prefix>rep</dcr:prefix><dcr:uri>internal</dcr:uri></dcr:namespace></dcr:namespaces></D:prop></D:set></D:propertyupdate>

HTTP/1.1 207 Multi Status
Content-Type: text/xml; charset=utf-8
Content-Length: 197
Server: Jetty(6.1.x)

<?xml version="1.0" encoding="UTF-8"?><D:multistatus xmlns:D="DAV:"><D:response><D:href>http://localhost:8080/server/tests/</D:href><D:status>HTTP/1.1 200 OK</D:status></D:response></D:multistatus>


validation can happen on backend side:


PROPPATCH /server/tests HTTP/1.1
Authorization: Basic YWRtaW46YWRtaW4=
User-Agent: Jakarta Commons-HttpClient/3.0
Host: localhost:8080
Content-Length: 2078
Content-Type: text/xml; charset=UTF-8

<?xml version="1.0" encoding="UTF-8"?>
  <D:propertyupdate xmlns:D="DAV:">
  <D:set>
   <D:prop><dcr:namespaces xmlns:dcr="http://www.day.com/jcr/webdav/1.0">
     <dcr:namespace>
       <dcr:prefix/>
       <dcr:uri/>
     </dcr:namespace>
     <dcr:namespace>
       <dcr:prefix>nt</dcr:prefix>
       <dcr:uri>http://www.jcp.org/jcr/nt/1.0</dcr:uri>
     </dcr:namespace>
     <dcr:namespace>
       <dcr:prefix>valid</dcr:prefix>
       <dcr:uri>http://www.jcp.org/jcr/1.0</dcr:uri>
     </dcr:namespace>
     <dcr:namespace>
       <dcr:prefix>test</dcr:prefix>
       <dcr:uri>http://liip.to/jackalope</dcr:uri>
     </dcr:namespace>
     <dcr:namespace>
       <dcr:prefix>xs</dcr:prefix>
       <dcr:uri>http://www.w3.org/2001/XMLSchema</dcr:uri>
     </dcr:namespace>
     <dcr:namespace>
       <dcr:prefix>xml</dcr:prefix>
       <dcr:uri>http://www.w3.org/XML/1998/namespace</dcr:uri>
     </dcr:namespace>
     <dcr:namespace>
       <dcr:prefix>crx</dcr:prefix>
       <dcr:uri>http://www.day.com/crx/1.0</dcr:uri>
     </dcr:namespace>
     <dcr:namespace>
       <dcr:prefix>fn_old</dcr:prefix>
       <dcr:uri>http://www.w3.org/2004/10/xpath-functions</dcr:uri>
     </dcr:namespace>
     <dcr:namespace>
       <dcr:prefix>mix</dcr:prefix><dcr:uri>http://www.jcp.org/jcr/mix/1.0</dcr:uri></dcr:namespace><dcr:namespace><dcr:prefix>lx</dcr:prefix><dcr:uri>http://flux-cms.org/2.0</dcr:uri></dcr:namespace><dcr:namespace><dcr:prefix>jcr</dcr:prefix><dcr:uri>http://www.jcp.org/jcr/1.0</dcr:uri></dcr:namespace><dcr:namespace><dcr:prefix>sling</dcr:prefix><dcr:uri>http://sling.apache.org/jcr/sling/1.0</dcr:uri></dcr:namespace><dcr:namespace><dcr:prefix>new_prefix</dcr:prefix><dcr:uri>http://a_new_namespace</dcr:uri></dcr:namespace><dcr:namespace><dcr:prefix>vlt</dcr:prefix><dcr:uri>http://www.day.com/jcr/vault/1.0</dcr:uri></dcr:namespace><dcr:namespace><dcr:prefix>my_prefix</dcr:prefix><dcr:uri>http://a_new_namespace</dcr:uri></dcr:namespace><dcr:namespace><dcr:prefix>sv</dcr:prefix><dcr:uri>http://www.jcp.org/jcr/sv/1.0</dcr:uri></dcr:namespace><dcr:namespace><dcr:prefix>fn</dcr:prefix><dcr:uri>http://www.w3.org/2005/xpath-functions</dcr:uri></dcr:namespace><dcr:namespace><dcr:prefix>rep</dcr:prefix><dcr:uri>internal</dcr:uri></dcr:namespace></dcr:namespaces></D:prop></D:set></D:propertyupdate>

HTTP/1.1 409 Conflict
Content-Type: text/xml; charset=utf-8
Content-Length: 308
Server: Jetty(6.1.x)

<?xml version="1.0" encoding="UTF-8"?><D:error xmlns:D="DAV:"><dcr:exception xmlns:dcr="http://www.day.com/jcr/webdav/1.0"><dcr:class>javax.jcr.NamespaceException</dcr:class><dcr:message>failed to register namespace valid -&gt; http://www.jcp.org/jcr/1.0: reserved URI</dcr:message></dcr:exception></D:error>
         */
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
        $this->namespaceManager->checkPrefix($prefix);
        if (! array_key_exists($prefix, $this->userNamespaces)) {
            //defaultNamespaces would throw an exception in checkPrefix already
            throw new \PHPCR\NamespaceException("Prefix $prefix is not currently registered");
        }
        throw new NotImplementedException('Write');
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
}
