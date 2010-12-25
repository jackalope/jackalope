<?php
/**
 * Class to gather namespace actions.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 *
 * @package jackalope
 */

namespace Jackalope;

/**
 * Class to gather namespace actions.
 *
 * @package jackalope
 *
 */
class NamespaceManager
{
    /**
     * List of predefined namespaces.
     * @var array
     */
    protected $defaultNamespaces = array();

    /**
     * Initializes the object to be instantiated.
     *
     * @param object $factory Ignored for now, as this class does not create objects
     * @param array $defaultNamespaces Set of predefined namespaces.
     */
    public function __construct($factory, $defaultNamespaces)
    {
        $this->defaultNamespaces = $defaultNamespaces;
    }

    /**
     * Verifies the correctness of the given prefix.
     *
     * Throws the \PHPCR\NamespaceException if an attempt is made to re-assign
     * a built-in prefix to a new URI or, to register a namespace with a prefix
     * that begins with the characters "xml" (in any combination of case)
     *
     * @return void
     *
     * @throws \PHPCR\NamespaceException if re-assign built-in prefix or prefix starting with xml
     */
    public function checkPrefix($prefix)
    {
        if (! strncasecmp('xml', $prefix, 3)) {
            throw new \PHPCR\NamespaceException('Do not use xml in prefixes for namespace changes');
        }
        if (array_key_exists($prefix, $this->defaultNamespaces)) {
            throw new \PHPCR\NamespaceException('Do not change the predefined prefixes');
        }
    }
}