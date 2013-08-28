<?php

namespace Jackalope\Transport;

use PHPCR\ReferentialIntegrityException;
use PHPCR\RepositoryException;
use PHPCR\UnsupportedRepositoryOperationException;
use PHPCR\Version\VersionException;

/**
 * Defines the methods needed for versioning support.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
interface VersioningInterface extends TransportInterface
{

    /**
     * Check-in item at path.
     *
     * @see VersionManager::checkin
     *
     * @param string $path absolute path to the node
     *
     * @return string path to the new version
     *
     * @throws UnsupportedRepositoryOperationException
     * @throws RepositoryException
     */
    public function checkinItem($path);

    /**
     * Check-out item at path.
     *
     * @see VersionManager::checkout
     *
     * @param string $path absolute path to the node
     *
     * @throws UnsupportedRepositoryOperationException
     * @throws RepositoryException
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
     * Remove a version given the path to the version node and the name of the version.
     *
     * @param string $versionPath absolute path to the version node
     * @param string $versionName The name of the version
     *
     * @throws ReferentialIntegrityException
     * @throws VersionException
     */
    public function removeVersion($versionPath, $versionName);

}
