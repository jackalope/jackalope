<?php

namespace Jackalope\Version;

use Jackalope\ObjectManager, Jackalope\NotImplementedException;

// inherit all doc
/**
 * @api
 */
class VersionManager implements \PHPCR\Version\VersionManagerInterface {

    /**
     * @var \Jackalope\ObjectManager
     */
    protected $objectmanager;
    /** @var object   The jackalope object factory for this object */
    protected $factory;

    /**
     * Create the version manager - there should be only one per session.
     *
     * @param object $factory an object factory implementing "get" as
     *      described in \Jackalope\Factory
     * @param ObjectManager $objectManager
     */
    public function __construct($factory, ObjectManager $objectmanager)
    {
        $this->objectmanager = $objectmanager;
        $this->factory = $factory;
    }

    // inherit all doc
    /**
     * @api
     */
     public function checkin($absPath)
     {
         //FIXME: make sure this doc above is correct:
         // If this node is already checked-in, this method has no effect but returns
         // the current base version of this node.
         return $this->objectmanager->checkin($absPath);
     }

    // inherit all doc
    /**
     * @api
     */
     public function checkout($absPath)
     {
         $this->objectmanager->checkout($absPath);
     }

    // inherit all doc
    /**
     * @api
     */
    public function checkpoint($absPath)
    {
        $version = $this->checkin($absPath); //just returns current version if already checked in
        $this->checkout($absPath);
        return $version;
    }

    // inherit all doc
    /**
     * @api
     */
    public function isCheckedOut($absPath)
    {
        throw new NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    public function getVersionHistory($absPath)
    {
        return $this->factory->get('Version\VersionHistory', array($this->objectmanager,$absPath));
    }

    // inherit all doc
    /**
     * @api
     */
    public function getBaseVersion($absPath)
    {
        $node = $this->objectmanager->getNodeByPath($absPath);
        try {
            //TODO: could check if node has versionable mixin type
            $uuid = $node->getProperty('jcr:baseVersion')->getString();
        } catch(\PHPCR\PathNotFoundException $e) {
            throw new \PHPCR\UnsupportedRepositoryOperationException("No jcr:baseVersion version for $path");
        }
        return $this->objectmanager->getNode($uuid, '/', 'Version\Version');
    }

    // inherit all doc
    /**
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

    // inherit all doc
    /**
     * @api
     */
    public function restoreByLabel($absPath, $versionLabel, $removeExisting)
    {
        throw new NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    public function merge($source, $srcWorkspace = null, $bestEffort = null, $isShallow = false)
    {
        throw new NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    public function doneMerge($absPath, \PHPCR\Version\VersionInterface $version)
    {
        throw new NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    public function cancelMerge($absPath, \PHPCR\Version\VersionInterface $version)
    {
        throw new NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    public function createConfiguration($absPath, \PHPCR\Version\VersionInterface $baseline)
    {
        throw new NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    public function setActivity(\PHPCR\NodeInterface $activity)
    {
        throw new NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    public function getActivity()
    {
        throw new NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    public function createActivity($title)
    {
        throw new NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    public function removeActivity(\PHPCR\NodeInterface $activityNode)
    {
        throw new NotImplementedException();
    }

}
