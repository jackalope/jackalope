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
    protected $objectmanager;
    /** @var FactoryInterface   The jackalope object factory for this object */
    protected $factory;

    /**
     * @var array of VersionHistory
     */
    protected $versionHistories = array();

    /**
     * Create the version manager - there should be only one per session.
     *
     * @param FactoryInterface $factory the object factory
     * @param ObjectManager $objectManager
     */
    public function __construct(FactoryInterface $factory, ObjectManager $objectmanager)
    {
        $this->objectmanager = $objectmanager;
        $this->factory = $factory;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
     public function checkin($absPath)
     {
         //FIXME: make sure this doc above is correct:
         // If this node is already checked-in, this method has no effect but returns
         // the current base version of this node.
         $version = $this->objectmanager->checkin($absPath);
         if (array_key_exists($absPath, $this->versionHistories)) {
             $this->versionHistories[$absPath]->notifyHistoryChanged();
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
         $this->objectmanager->checkout($absPath);
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
        if (! isset($this->versionHistories[$absPath])) {
            $this->versionHistories[$absPath] = $this->factory->get('Version\\VersionHistory', array($this->objectmanager, $absPath));
        }
        return $this->versionHistories[$absPath];
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getBaseVersion($absPath)
    {
        $node = $this->objectmanager->getNodeByPath($absPath);
        try {
            //TODO: could check if node has versionable mixin type
            $uuid = $node->getProperty('jcr:baseVersion')->getString();
        } catch(PathNotFoundException $e) {
            throw new UnsupportedRepositoryOperationException("No jcr:baseVersion version for $absPath");
        }
        return $this->objectmanager->getNode($uuid, '/', 'Version\\Version');
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
        $this->objectmanager->restore($removeExisting, $vpath, $absPath);
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
