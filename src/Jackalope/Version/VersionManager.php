<?php

namespace Jackalope\Version;

use Jackalope\FactoryInterface;
use Jackalope\NotImplementedException;
use Jackalope\ObjectManager;
use PHPCR\InvalidItemStateException;
use PHPCR\ItemNotFoundException;
use PHPCR\NodeInterface;
use PHPCR\NoSuchWorkspaceException;
use PHPCR\PathNotFoundException;
use PHPCR\UnsupportedRepositoryOperationException;
use PHPCR\Util\PathHelper;
use PHPCR\ValueFormatException;
use PHPCR\Version\ActivityViolationException;
use PHPCR\Version\VersionHistoryInterface;
use PHPCR\Version\VersionInterface;
use PHPCR\Version\VersionManagerInterface;

/**
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
final class VersionManager implements VersionManagerInterface
{
    private ObjectManager $objectManager;

    public function __construct(FactoryInterface $factory, ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @api
     */
    public function checkin($absPath): VersionInterface
    {
        if ($node = $this->objectManager->getCachedNode($absPath)) {
            if ($node->isModified()) {
                throw new InvalidItemStateException("You may not checkin node at $absPath with pending unsaved changes");
            }
        }

        $version = $this->objectManager->checkin($absPath);
        $version->setCachedPredecessorsDirty();
        if ($history = $this->objectManager->getCachedNode(PathHelper::getParentPath($version->getPath()), VersionHistory::class)) {
            $history->notifyHistoryChanged();
        }
        if ($node) {
            // OPTIMIZE: set property jcr:isCheckedOut on node directly? but without triggering write on save()
            $node->setDirty();
        }

        return $version;
    }

    /**
     * @api
     */
    public function checkout($absPath): void
    {
        $this->objectManager->checkout($absPath);
        if ($node = $this->objectManager->getCachedNode($absPath)) {
            // OPTIMIZE: set property jcr:isCheckedOut on node directly? but without triggering write on save()
            $node->setDirty();
        }
    }

    /**
     * @throws ActivityViolationException
     *
     * @api
     */
    public function checkpoint($absPath): VersionInterface
    {
        $version = $this->checkin($absPath); // just returns current version if already checked in
        $this->checkout($absPath);

        return $version;
    }

    /**
     * @api
     */
    public function isCheckedOut($absPath): bool
    {
        $node = $this->objectManager->getNodeByPath($absPath);
        if (!$node->isNodeType('mix:simpleVersionable')) {
            throw new UnsupportedRepositoryOperationException("Node at $absPath is not versionable");
        }

        return $node->getPropertyValue('jcr:isCheckedOut');
    }

    /**
     * @throws ItemNotFoundException
     * @throws NoSuchWorkspaceException
     * @throws \InvalidArgumentException
     * @throws PathNotFoundException
     * @throws ValueFormatException
     *
     * @api
     */
    public function getVersionHistory($absPath): VersionHistoryInterface
    {
        $node = $this->objectManager->getNodeByPath($absPath);
        if (!$node->isNodeType('mix:simpleVersionable')) {
            throw new UnsupportedRepositoryOperationException("Node at $absPath is not versionable");
        }

        return $this->objectManager->getNodeByIdentifier($node->getProperty('jcr:versionHistory')->getString(), VersionHistory::class);
    }

    /**
     * @api
     */
    public function removeVersionHistory($absPath): void
    {
        throw new UnsupportedRepositoryOperationException('Removing the version history is not supported.');
    }

    /**
     * @throws ItemNotFoundException
     * @throws \InvalidArgumentException
     * @throws ValueFormatException
     * @throws NoSuchWorkspaceException
     *
     * @api
     */
    public function getBaseVersion($absPath): VersionInterface
    {
        $node = $this->objectManager->getNodeByPath($absPath);
        try {
            // TODO: could check if node has versionable mixin type
            $uuid = $node->getProperty('jcr:baseVersion')->getString();
        } catch (PathNotFoundException $e) {
            throw new UnsupportedRepositoryOperationException("No jcr:baseVersion version for $absPath");
        }

        return $this->objectManager->getNodeByIdentifier($uuid, Version::class);
    }

    /**
     * @throws \InvalidArgumentException
     *
     * @api
     */
    public function restore($removeExisting, $version, $absPath = null): void
    {
        if ($this->objectManager->hasPendingChanges()) {
            throw new InvalidItemStateException('You may not call restore when there pending unsaved changes');
        }

        if (is_string($version)) {
            if (!is_string($absPath)) {
                throw new \InvalidArgumentException('To restore version by version name you need to specify the path to the node you want to restore to this name');
            }
            $vh = $this->getVersionHistory($absPath);
            $version = $vh->getVersion($version);
            $versionPath = $version->getPath();
            $nodePath = $absPath;
        } elseif (is_array($version)) {
            throw new NotImplementedException('TODO: implement restoring a list of versions');
        } elseif ($version instanceof VersionInterface && is_string($absPath)) {
            throw new NotImplementedException('TODO: implement restoring a version to a specified path');
        } elseif ($version instanceof VersionInterface) {
            $versionPath = $version->getPath();
            $nodePath = $this->objectManager->getNodeByIdentifier($version->getContainingHistory()->getVersionableIdentifier())->getPath();
        } else {
            throw new \InvalidArgumentException();
        }

        $this->objectManager->restore($removeExisting, $versionPath, $nodePath);

        $version->setCachedPredecessorsDirty();
        if ($history = $this->objectManager->getCachedNode(PathHelper::getParentPath($version->getPath()), VersionHistory::class)) {
            $history->notifyHistoryChanged();
        }
    }

    /**
     * @throws \InvalidArgumentException
     * @throws ItemNotFoundException
     * @throws NoSuchWorkspaceException
     * @throws PathNotFoundException
     * @throws ValueFormatException
     *
     * @api
     */
    public function restoreByLabel($absPath, $versionLabel, $removeExisting): void
    {
        $vh = $this->getVersionHistory($absPath);
        $version = $vh->getVersionByLabel($versionLabel);
        $this->restore($removeExisting, $version);
    }

    /**
     * @api
     */
    public function merge($source, $srcWorkspace = null, $bestEffort = null, $isShallow = false)
    {
        // @codeCoverageIgnoreStart
        throw new NotImplementedException();
        // @codeCoverageIgnoreEnd
    }

    /**
     * @api
     */
    public function doneMerge($absPath, VersionInterface $version)
    {
        // @codeCoverageIgnoreStart
        throw new NotImplementedException();
        // @codeCoverageIgnoreEnd
    }

    /**
     * @api
     */
    public function cancelMerge($absPath, VersionInterface $version)
    {
        // @codeCoverageIgnoreStart
        throw new NotImplementedException();
        // @codeCoverageIgnoreEnd
    }

    /**
     * @api
     */
    public function createConfiguration($absPath, VersionInterface $baseline)
    {
        // @codeCoverageIgnoreStart
        throw new NotImplementedException();
        // @codeCoverageIgnoreEnd
    }

    /**
     * @api
     */
    public function setActivity(NodeInterface $activity)
    {
        // @codeCoverageIgnoreStart
        throw new NotImplementedException();
        // @codeCoverageIgnoreEnd
    }

    /**
     * @api
     */
    public function getActivity()
    {
        // @codeCoverageIgnoreStart
        throw new NotImplementedException();
        // @codeCoverageIgnoreEnd
    }

    /**
     * @api
     */
    public function createActivity($title)
    {
        // @codeCoverageIgnoreStart
        throw new NotImplementedException();
        // @codeCoverageIgnoreEnd
    }

    /**
     * @api
     */
    public function removeActivity(NodeInterface $activityNode)
    {
        // @codeCoverageIgnoreStart
        throw new NotImplementedException();
        // @codeCoverageIgnoreEnd
    }
}
