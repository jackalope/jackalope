<?php

namespace Jackalope\Version;

use PHPCR\Version\VersionInterface;

use Jackalope\NotImplementedException;
use Jackalope\Node;

// inherit all doc
/**
 * @api
 */
class Version extends Node implements VersionInterface {

    // inherit all doc
    /**
     * @api
     */
    public function getContainingHistory()
    {
       throw new NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    public function getCreated()
    {
        throw new NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    public function getLinearSuccessor()
    {
        throw new NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    public function getSuccessors()
    {
        if (! $this->hasProperty('jcr:successors')) {
            // no successors
            return array();
        }

        /* predecessors is a multivalue property with REFERENCE.
         * get it as string so we can create the Version instances from uuid
         * with the objectmanager
         */
        $successors = $this->getProperty("jcr:successors")->getString();
        $results = array();
        if ($successors) {
            foreach ($successors as $uuid) {
                $results[] = $this->objectManager->getNode($uuid, '/', 'Version\\Version');
            }
        }
        return $results;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getLinearPredecessor()
    {
        throw new NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    public function getPredecessors()
    {
        if (! $this->hasProperty('jcr:predecessors')) {
            // no predecessors
            return array();
        }

        /*
         * predecessors is a multivalue property with REFERENCE.
         * get it as string so we can create the Version instances from uuid
         * with the objectmanager. see 3.13.2.6
         */
        $predecessors = $this->getProperty("jcr:predecessors")->getString();
        $results = array();
        foreach ($predecessors as $uuid) {
            $results[] = $this->objectManager->getNode($uuid, '/', 'Version\\Version');
        }
        return $results;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getFrozenNode()
    {
        $frozen = $this->getNode('jcr:frozenNode');
        //TODO: what should we do now? recreate the node with the data at that time?
        throw new NotImplementedException();
    }
}
