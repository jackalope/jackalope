<?php

namespace Jackalope\Version;

use PHPCR\Version\VersionInterface;

use Jackalope\NotImplementedException;
use Jackalope\Node;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
class Version extends Node implements VersionInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getContainingHistory()
    {
        return $this->objectManager->getNode($this->parentPath, '/', 'Version\\VersionHistory');
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getCreated()
    {
        return $this->getProperty('jcr:created')->getValue();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getLinearSuccessor()
    {
        $successors = $this->getProperty("jcr:successors")->getString();
        if (count($successors) > 1) {
            // @codeCoverageIgnoreStart
            throw new NotImplementedException('TODO: handle non-trivial case when there is a choice of successors to find the linear from');
            // @codeCoverageIgnoreEnd
        }
        if (count($successors) == 0) {
            return null; // no successor
        }
        $uuid = reset($successors);

        return $this->objectManager->getNodeByIdentifier($uuid, 'Version\\Version');
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getSuccessors()
    {
        /* successor is a multivalue property with REFERENCE.
         * get it as string so we can create the Version instances from uuid
         * with the objectManager
         */
        $successors = $this->getProperty("jcr:successors")->getString();
        $results = array();
        foreach ($successors as $uuid) {
            // OPTIMIZE: use objectManager->getNodes instead of looping
            $results[] = $this->objectManager->getNodeByIdentifier($uuid, 'Version\\Version');
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
        $predecessor = $this->getProperty("jcr:predecessors")->getString();
        if (count($predecessor) > 1) {
            // @codeCoverageIgnoreStart
            throw new NotImplementedException('TODO: handle non-trivial case when there is a choice of successors to find the linear from');
            // @codeCoverageIgnoreEnd
        }
        if (count($predecessor) == 0) {
            return null; // no successor
        }
        $uuid = reset($predecessor);

        return $this->objectManager->getNodeByIdentifier($uuid, 'Version\\Version');
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getPredecessors()
    {
        /*
         * predecessors is a multivalue property with REFERENCE.
         * get it as string so we can create the Version instances from uuid
         * with the objectManager. see 3.13.2.6
         */
        $predecessors = $this->getProperty("jcr:predecessors")->getString();
        $results = array();
        foreach ($predecessors as $uuid) {
            // OPTIMIZE: use objectManager->getNodes instead of looping
            $results[] = $this->objectManager->getNodeByIdentifier($uuid, 'Version\\Version');
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
        foreach ($this->getProperty('jcr:predecessors')->getString() as $preuuid) {
            $pre = $this->objectManager->getCachedNodeByUuid($preuuid, 'Version\\Version');
            if ($pre) {
                $pre->setDirty();
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
        // only set other versions dirty if they are cached, no need to load them from backend just to tell they need to be reloaded
        foreach ($this->getProperty('jcr:successors')->getString() as $postuuid) {
            $post = $this->objectManager->getCachedNodeByUuid($postuuid, 'Version\\Version');
            if ($post) {
                $post->setDirty();
            }
        }
    }
}
