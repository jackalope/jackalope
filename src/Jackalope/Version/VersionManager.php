<?php

namespace Jackalope\Version;

use InvalidArgumentException;

use PHPCR\NodeInterface;
use PHPCR\Util\PathHelper;
use PHPCR\PathNotFoundException;
use PHPCR\UnsupportedRepositoryOperationException;
use PHPCR\InvalidItemStateException;

use PHPCR\Version\VersionInterface;
use PHPCR\Version\VersionManagerInterface;

use Jackalope\ObjectManager;
use Jackalope\NotImplementedException;
use Jackalope\FactoryInterface;

/**
 * {@inheritDoc}
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
class VersionManager implements VersionManagerInterface
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;
    /** @var FactoryInterface   The jackalope object factory for this object */
    protected $factory;

    /**
     * Create the version manager - there should be only one per session.
     *
     * @param FactoryInterface $factory       the object factory
     * @param ObjectManager    $objectManager
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
         if ($node = $this->objectManager->getCachedNode($absPath)) {
             if ($node->isModified()) {
                 throw new InvalidItemStateException("You may not checkin node at $absPath with pending unsaved changes");
             }
         }

         $version = $this->objectManager->checkin($absPath);
         $version->setCachedPredecessorsDirty();
         if ($history = $this->objectManager->getCachedNode(PathHelper::getParentPath($version->getPath()), 'Version\\VersionHistory')) {
             $history->notifyHistoryChanged();
         }
         if ($node) {
             // OPTIMIZE: set property jcr:isCheckedOut on node directly? but without triggering write on save()
             $node->setDirty();
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
         if ($node = $this->objectManager->getCachedNode($absPath)) {
             // OPTIMIZE: set property jcr:isCheckedOut on node directly? but without triggering write on save()
             $node->setDirty();
         }
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
        $node = $this->objectManager->getNodeByPath($absPath);
        if (! $node->isNodeType('mix:simpleVersionable')) {
            throw new UnsupportedRepositoryOperationException("Node at $absPath is not versionable");
        }

        return $node->getPropertyValue('jcr:isCheckedOut');
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getVersionHistory($absPath)
    {
        $node = $this->objectManager->getNodeByPath($absPath);
        if (! $node->isNodeType('mix:simpleVersionable')) {
            throw new UnsupportedRepositoryOperationException("Node at $absPath is not versionable");
        }

        return $this->objectManager->getNodeByIdentifier($node->getProperty('jcr:versionHistory')->getString(), 'Version\\VersionHistory');
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function removeVersionHistory($absPath)
    {
        throw new UnsupportedRepositoryOperationException('Removing the version history is not supported.');
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
        } catch (PathNotFoundException $e) {
            throw new UnsupportedRepositoryOperationException("No jcr:baseVersion version for $absPath");
        }

        return $this->objectManager->getNodeByIdentifier($uuid, 'Version\\Version');
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function restore($removeExisting, $version, $absPath = null)
    {
        if ($this->objectManager->hasPendingChanges()) {
            throw new InvalidItemStateException('You may not call restore when there pending unsaved changes');
        }

        if (is_string($version)) {
            if (! is_string($absPath)) {
                throw new InvalidArgumentException('To restore version by version name you need to specify the path to the node you want to restore to this name');
            }
            $vh = $this->getVersionHistory($absPath);
            $version = $vh->getVersion($version);
            $versionPath = $version->getPath();
            $nodePath = $absPath;

        } elseif (is_array($version)) {
            // @codeCoverageIgnoreStart
            throw new NotImplementedException('TODO: implement restoring a list of versions');
            // @codeCoverageIgnoreEnd

        } elseif ($version instanceof VersionInterface && is_string($absPath)) {
            // @codeCoverageIgnoreStart
            throw new NotImplementedException('TODO: implement restoring a version to a specified path');
            // @codeCoverageIgnoreEnd

        } elseif ($version instanceof VersionInterface) {
            $versionPath = $version->getPath();
            $nodePath = $this->objectManager->getNodeByIdentifier($version->getContainingHistory()->getVersionableIdentifier())->getPath();

        } else {
            throw new InvalidArgumentException();
        }

        $this->objectManager->restore($removeExisting, $versionPath, $nodePath);

        $version->setCachedPredecessorsDirty();
        if ($history = $this->objectManager->getCachedNode(PathHelper::getParentPath($version->getPath()), 'Version\\VersionHistory')) {
            $history->notifyHistoryChanged();
        }
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function restoreByLabel($absPath, $versionLabel, $removeExisting)
    {
        // @codeCoverageIgnoreStart
        throw new NotImplementedException();
        // @codeCoverageIgnoreEnd
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function merge($source, $srcWorkspace = null, $bestEffort = null, $isShallow = false)
    {
        // @codeCoverageIgnoreStart
        throw new NotImplementedException();
        // @codeCoverageIgnoreEnd
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function doneMerge($absPath, VersionInterface $version)
    {
        // @codeCoverageIgnoreStart
        throw new NotImplementedException();
        // @codeCoverageIgnoreEnd
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function cancelMerge($absPath, VersionInterface $version)
    {
        // @codeCoverageIgnoreStart
        throw new NotImplementedException();
        // @codeCoverageIgnoreEnd
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function createConfiguration($absPath, VersionInterface $baseline)
    {
        // @codeCoverageIgnoreStart
        throw new NotImplementedException();
        // @codeCoverageIgnoreEnd
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setActivity(NodeInterface $activity)
    {
        // @codeCoverageIgnoreStart
        throw new NotImplementedException();
        // @codeCoverageIgnoreEnd
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getActivity()
    {
        // @codeCoverageIgnoreStart
        throw new NotImplementedException();
        // @codeCoverageIgnoreEnd
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function createActivity($title)
    {
        // @codeCoverageIgnoreStart
        throw new NotImplementedException();
        // @codeCoverageIgnoreEnd
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function removeActivity(NodeInterface $activityNode)
    {
        // @codeCoverageIgnoreStart
        throw new NotImplementedException();
        // @codeCoverageIgnoreEnd
    }

}
