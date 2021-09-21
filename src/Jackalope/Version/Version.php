<?php

namespace Jackalope\Version;

use InvalidArgumentException;
use Jackalope\Node;
use Jackalope\NotImplementedException;
use PHPCR\ItemNotFoundException;
use PHPCR\NoSuchWorkspaceException;
use PHPCR\PathNotFoundException;
use PHPCR\RepositoryException;
use PHPCR\Version\VersionInterface;

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
        return $this->objectManager->getNode($this->parentPath, '/', VersionHistory::class);
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     * @throws PathNotFoundException
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
     * @throws InvalidArgumentException
     * @throws PathNotFoundException
     *
     * @api
     */
    public function getLinearSuccessor()
    {
        $successors = $this->getProperty('jcr:successors')->getString();
        if (count($successors) > 1) {
            // @codeCoverageIgnoreStart
            throw new NotImplementedException('TODO: handle non-trivial case when there is a choice of successors to find the linear from');
            // @codeCoverageIgnoreEnd
        }
        if (0 === count($successors)) {
            return null; // no successor
        }
        $uuid = reset($successors);

        return $this->objectManager->getNodeByIdentifier($uuid, Version::class);
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     * @throws PathNotFoundException
     *
     * @api
     */
    public function getSuccessors()
    {
        /* successor is a multivalue property with REFERENCE.
         * get it as string so we can create the Version instances from uuid
         * with the objectManager
         */
        $successors = $this->getProperty('jcr:successors')->getString();
        $results = [];
        foreach ($successors as $uuid) {
            // OPTIMIZE: use objectManager->getNodes instead of looping
            $results[] = $this->objectManager->getNodeByIdentifier($uuid, Version::class);
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     * @throws PathNotFoundException
     * @throws ItemNotFoundException
     * @throws NoSuchWorkspaceException
     *
     * @api
     */
    public function getLinearPredecessor()
    {
        $predecessor = $this->getProperty('jcr:predecessors')->getString();
        if (count($predecessor) > 1) {
            // @codeCoverageIgnoreStart
            throw new NotImplementedException('TODO: handle non-trivial case when there is a choice of successors to find the linear from');
            // @codeCoverageIgnoreEnd
        }
        if (0 === count($predecessor)) {
            return null; // no successor
        }
        $uuid = reset($predecessor);

        return $this->objectManager->getNodeByIdentifier($uuid, Version::class);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \InvalidArgumentException
     * @throws PathNotFoundException
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
        $predecessors = $this->getProperty('jcr:predecessors')->getString();
        $results = [];
        foreach ($predecessors as $uuid) {
            // OPTIMIZE: use objectManager->getNodes instead of looping
            $results[] = $this->objectManager->getNodeByIdentifier($uuid, Version::class);
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
        throw new RepositoryException('You can not remove a version like this, use VersionHistory.removeVersion()');
    }

    /**
     * Set all cached predecessors of this version dirty.
     *
     * @private
     */
    public function setCachedPredecessorsDirty()
    {
        // only set other versions dirty if they are cached, no need to load them from backend just to tell they need to be reloaded
        foreach ($this->getProperty('jcr:predecessors')->getString() as $preuuid) {
            $pre = $this->objectManager->getCachedNodeByUuid($preuuid, Version::class);
            if ($pre) {
                $pre->setDirty();
            }
        }
    }

    /**
     * Set all cached successors of this version dirty.
     *
     * @private
     */
    public function setCachedSuccessorsDirty()
    {
        // only set other versions dirty if they are cached, no need to load them from backend just to tell they need to be reloaded
        foreach ($this->getProperty('jcr:successors')->getString() as $postuuid) {
            $post = $this->objectManager->getCachedNodeByUuid($postuuid, Version::class);
            if ($post) {
                $post->setDirty();
            }
        }
    }
}
