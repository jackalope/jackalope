<?php

namespace Jackalope\Version;

use PHPCR\Version\VersionInterface;

use Jackalope\NotImplementedException;
use Jackalope\Node;

/**
 * {@inheritDoc}
 *
 * @api
 */
class Version extends Node implements VersionInterface {

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getContainingHistory()
    {
       throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getCreated()
    {
        if (!$this->hasProperty('jcr:created')) {
            return null;
        }

        return $this->getProperty('jcr:created')->getValue();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getLinearSuccessor()
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
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
         * with the objectManager
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

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getLinearPredecessor()
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
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
         * with the objectManager. see 3.13.2.6
         */
        $predecessors = $this->getProperty("jcr:predecessors")->getString();
        $results = array();
        foreach ($predecessors as $uuid) {
            $results[] = $this->objectManager->getNode($uuid, '/', 'Version\\Version');
        }
        return $results;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getFrozenNode()
    {
        return $this->getNode('jcr:frozenNode');
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function remove()
    {
        // A version node cannot be removed, so always throw an Exception
        throw new \PHPCR\RepositoryException('You can not remove a version like this, use VersionHistory.removeVersion()');
    }

    /**
     * Set all cached predecessors of this version dirty
     *
     * @private
     */
    public function setCachedPredecessorsDirty()
    {
        // only set other versions dirty if they are cached, no need to load them from backend just to tell they need to be reloaded
        if ($this->hasProperty('jcr:predecessors')) {
            foreach ($this->getProperty('jcr:predecessors')->getString() as $preuuid) {
                $pre = $this->objectManager->getCachedNodeByUuid($preuuid, 'Version\\Version');
                if ($pre) {
                    $pre->setDirty();
                }
            }
        }

    }

    /**
     * Set all cached successors of this version dirty
     *
     * @private
     */
    public function setCachedSuccessorsDirty()
    {
        if ($this->hasProperty('jcr:successor')) {
            foreach($this->getProperty('jcr:successor')->getString() as $postuuid) {
                $post = $this->objectManager->getCachedNodeByUuid($postuuid, 'Version\\Version');
                if ($post) {
                    $post->setDirty();
                }
            }
        }
    }
}
