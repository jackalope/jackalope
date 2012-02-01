<?php

namespace Jackalope\Transport;

/**
 * Defines the method needed for versioning support.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
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
     * @throws PHPCR\UnsupportedRepositoryOperationException
     * @throws PHPCR\RepositoryException
     */
    function checkinItem($path);

    /**
     * Check-out item at path.
     *
     * @see VersionManager::checkout
     *
     * @param string $path absolute path to the node
     *
     * @return void
     *
     * @throws PHPCR\UnsupportedRepositoryOperationException
     * @throws PHPCR\RepositoryException
     */
    function checkoutItem($path);

    /**
     * Restore the item at versionPath to the location path
     *
     * TODO: This is incomplete. Needs batch processing to avoid chicken-and-egg problems
     *
     * @see VersionManager::restoreItem
     */
    function restoreItem($removeExisting, $versionPath, $path);

    /**
     * Remove a version given the path to the version node and the name of the version.
     *
     * @param $versionPath absolute path to the version node
     * @param $versionName The name of the version
     *
     * @throws PHPCR\ReferentialIntegrityException
     * @throws PHPCR\Version\VersionException
     */
    function removeVersion($versionPath, $versionName);

}
