<?php

namespace Jackalope;

use Jackalope\NodeType\NodeTypeManager;

/**
 * Implementation specific interface for implementing transactional transport
 * layers.
 *
 * Jackalope encapsulates all communication with the storage backend within
 * this interface.
 *
 * Adds the methods necessary for version handling
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 */
interface ReferenceableTransportInterface extends TransportInterface
{
    /**
     * Returns the path of all accessible REFERENCE properties in the workspace
     * that point to the node
     *
     * @param string $path
     * @param string $name name of referring REFERENCE properties to be returned;
     *       if null then all referring REFERENCEs are returned
     *
     * @return array
     */
    public function getReferences($path, $name = null);

    /**
     * Returns the path of all accessible WEAKREFERENCE properties in the
     * workspace that point to the node
     *
     * @param string $path
     * @param string $name name of referring WEAKREFERENCE properties to be
     *      returned; if null then all referring WEAKREFERENCEs are returned
     *
     * @return array
     */
    public function getWeakReferences($path, $name = null);
}
