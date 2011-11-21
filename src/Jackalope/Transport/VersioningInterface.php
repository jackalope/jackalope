<?php

namespace Jackalope\Transport;

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
interface VersioningInterface extends ReferenceInterface
{

    /**
     * Check-in item at path.
     *
     * @see VersionManager::checkin
     *
     * @param string $path
     *
     * @return string path to the new version
     *
     * @throws PHPCR\UnsupportedRepositoryOperationException
     * @throws PHPCR\RepositoryException
     */
    public function checkinItem($path);

    /**
     * Check-out item at path.
     *
     * @see VersionManager::checkout
     *
     * @param string $path
     *
     * @return void
     *
     * @throws PHPCR\UnsupportedRepositoryOperationException
     * @throws PHPCR\RepositoryException
     */
    public function checkoutItem($path);

    /**
     * Restore the item at versionPath to the location path
     *
     * TODO: This is incomplete. Needs batch processing to avoid chicken-and-egg problems
     *
     * @see VersionManager::restoreItem
     */
    public function restoreItem($removeExisting, $versionPath, $path);

    /**
     * Get the uuid of the version history node at $path
     *
     * @param string $path the path to the node we want the version
     *
     * @return string uuid of the version history node
     *
     * TODO: Does this make any sense? We should maybe return the root version to make this more generic.
     */
    public function getVersionHistory($path);
}
