<?php

namespace Jackalope\Version;

use PHPCR\NodeInterface;
use PHPCR\PathNotFoundException;
use PHPCR\UnsupportedRepositoryOperationException;

use PHPCR\Version\VersionInterface;
use PHPCR\Version\VersionManagerInterface;

use Jackalope\ObjectManager;
use Jackalope\NotImplementedException;
use Jackalope\FactoryInterface;

/**
 * {@inheritDoc}
 *
 * @api
 */
class VersionManager implements VersionManagerInterface {

    /**
     * @var ObjectManager
     */
    protected $objectManager;
    /** @var FactoryInterface   The jackalope object factory for this object */
    protected $factory;

    /**
     * Create the version manager - there should be only one per session.
     *
     * @param FactoryInterface $factory the object factory
     * @param ObjectManager $objectManager
     */
    public function __construct(FactoryInterface $factory, ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
        $this->factory = $factory;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
     public function checkin($absPath)
     {
         $version = $this->objectManager->checkin($absPath);
         $version->setCachedPredecessorsDirty();
         if ($history = $this->objectManager->getCachedNode(dirname($version->getPath()), 'Version\\VersionHistory')) {
             $history->notifyHistoryChanged();
         }
         return $version;
     }

    /**
     * {@inheritDoc}
     *
     * @api
     */
     public function checkout($absPath)
     {
         $this->objectManager->checkout($absPath);
     }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function checkpoint($absPath)
    {
        $version = $this->checkin($absPath); //just returns current version if already checked in
        $this->checkout($absPath);
        return $version;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isCheckedOut($absPath)
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getVersionHistory($absPath)
    {
        $node = $this->objectManager->getNode($absPath);
        if (! $node->isNodeType('mix:simpleVersionable')) {
            throw new UnsupportedRepositoryOperationException("Node at $absPath is not versionable");
        }

        return $this->objectManager->getNode($node->getProperty('jcr:versionHistory')->getString(), '/', 'Version\\VersionHistory');
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getBaseVersion($absPath)
    {
        $node = $this->objectManager->getNodeByPath($absPath);
        try {
            //TODO: could check if node has versionable mixin type
            $uuid = $node->getProperty('jcr:baseVersion')->getString();
        } catch(PathNotFoundException $e) {
            throw new UnsupportedRepositoryOperationException("No jcr:baseVersion version for $absPath");
        }
        return $this->objectManager->getNode($uuid, '/', 'Version\\Version');
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function restore($removeExisting, $version, $absPath = null)
    {
        //FIXME: This does not handle all cases
        if (! $absPath) {
            throw new NotImplementedException();
        }
        if (! is_string($version)) {
            throw new NotImplementedException();
        }
        $vh = $this->getVersionHistory($absPath);
        $version = $vh->getVersion($version);
        $vpath = $version->getPath();
        $this->objectManager->restore($removeExisting, $vpath, $absPath);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function restoreByLabel($absPath, $versionLabel, $removeExisting)
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function merge($source, $srcWorkspace = null, $bestEffort = null, $isShallow = false)
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function doneMerge($absPath, VersionInterface $version)
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function cancelMerge($absPath, VersionInterface $version)
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function createConfiguration($absPath, VersionInterface $baseline)
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setActivity(NodeInterface $activity)
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getActivity()
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function createActivity($title)
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function removeActivity(NodeInterface $activityNode)
    {
        throw new NotImplementedException();
    }

}
