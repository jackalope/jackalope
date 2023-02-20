<?php

namespace Jackalope\Version;

use Jackalope\Node;
use Jackalope\Property;
use PHPCR\RepositoryException;
use PHPCR\Version\VersionException;
use PHPCR\Version\VersionHistoryInterface;
use PHPCR\Version\VersionInterface;

/**
 * {@inheritDoc}
 *
 * A special node that represents a nt:versionHistory node.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 */
final class VersionHistory extends Node implements VersionHistoryInterface
{
    /**
     * Cache of all versions to only build the list once.
     */
    private array $versions;

    /**
     * Cache of the linear versions to only build the list once.
     */
    private array $linearVersions;

    /**
     * Cache of the version labels.
     */
    private array $versionLabels;

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getVersionableIdentifier(): string
    {
        return $this->getPropertyValue('jcr:versionableUuid');
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getRootVersion(): VersionInterface
    {
        return $this->objectManager->getNode('jcr:rootVersion', $this->getPath(), Version::class);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAllLinearVersions(): \Iterator
    {
        // OPTIMIZE: special iterator that delays loading the versions
        if (!isset($this->linearVersions)) {
            $this->linearVersions = [];
            $version = $this->getRootVersion();
            do {
                $this->linearVersions[$version->getName()] = $version;
            } while ($version = $version->getLinearSuccessor());
        }

        return new \ArrayIterator($this->linearVersions);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAllVersions(): \Iterator
    {
        // OPTIMIZE: special iterator that delays loading the versions
        if (!isset($this->versions)) {
            $rootVersion = $this->getRootVersion();
            $results = [$rootVersion->getName() => $rootVersion];
            $this->versions = array_merge($results, $this->getEventualSuccessors($rootVersion));
        }

        return new \ArrayIterator($this->versions);
    }

    /**
     * Walk along the successors line to get all versions of this node.
     *
     * According to spec, 3.13.1.4, these are called eventual successors
     *
     * @param VersionInterface $node the node to get successors
     *                               from
     *
     * @return array list of VersionInterface
     *
     * @throws RepositoryException
     */
    private function getEventualSuccessors($node): array
    {
        $successors = $node->getSuccessors();
        $results = [];
        foreach ($successors as $successor) {
            $results[$successor->getName()] = $successor;
            // OPTIMIZE: use a stack instead of recursion
            $results = array_merge($results, $this->getEventualSuccessors($successor));
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAllLinearFrozenNodes(): \Iterator
    {
        // OPTIMIZE: special iterator that delays loading frozen nodes
        $frozenNodes = [];
        foreach ($this->getAllLinearVersions() as $version) {
            $frozenNodes[$version->getName()] = $version->getFrozenNode();
        }

        return new \ArrayIterator($frozenNodes);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAllFrozenNodes(): \Iterator
    {
        // OPTIMIZE: special iterator that delays loading frozen nodes
        $frozenNodes = [];
        foreach ($this->getAllVersions() as $version) {
            $frozenNodes[$version->getName()] = $version->getFrozenNode();
        }

        return new \ArrayIterator($frozenNodes);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getVersion($versionName): VersionInterface
    {
        $this->getAllVersions();
        if (array_key_exists($versionName, $this->versions)) {
            return $this->versions[$versionName];
        }

        throw new VersionException("No version '$versionName'");
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getVersionByLabel($label): VersionInterface
    {
        if (!$this->hasVersionLabel($label)) {
            throw new VersionException("No label '$label'");
        }

        return $this->versionLabels[$label];
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function addVersionLabel($versionName, $label, $moveLabel): void
    {
        $this->initVersionLabels();
        $version = $this->getVersion($versionName);
        $path = $version->getPath();

        $this->objectManager->addVersionLabel($path, $label, $moveLabel);
        $this->versionLabels[$label] = $version;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function removeVersionLabel($label): void
    {
        if (!$this->hasVersionLabel($label)) {
            throw new VersionException("No label '$label'");
        }

        $version = $this->versionLabels[$label];
        $this->objectManager->removeVersionLabel($version->getPath(), $label);
        unset($this->versionLabels[$label]);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function hasVersionLabel($label, $version = null): bool
    {
        $labels = $this->getVersionLabels($version);

        return in_array($label, $labels, true);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getVersionLabels($version = null): array
    {
        $this->initVersionLabels();
        if (null === $version) {
            return array_keys($this->versionLabels);
        }

        $versions = $this->getAllVersions();
        $versionIsInHistory = false;

        foreach ($versions as $versionCheck) {
            /* @var VersionInterface $versionCheck */
            if ($versionCheck->getIdentifier() == $version->getIdentifier()) {
                $versionIsInHistory = true;
                break;
            }
        }

        if (!$versionIsInHistory) {
            throw new VersionException(sprintf('Version %s not found in history of %s', $version->getIdentifier(), $this->getPath()));
        }

        $result = [];
        foreach ($this->versionLabels as $label => $labelVersion) {
            /* @var VersionInterface $labelVersion */
            if ($labelVersion->getIdentifier() == $version->getIdentifier()) {
                $result[] = $label;
            }
        }

        return $result;
    }

    /**
     * This method fetches all version labels, if the cache array is not initialized yet.
     *
     * @throws RepositoryException
     */
    private function initVersionLabels(): void
    {
        if (isset($this->versionLabels)) {
            return;
        }

        $this->versionLabels = [];

        $node = $this->getNode('jcr:versionLabels');
        foreach ($node->getProperties() as $property) {
            /* @var Property $property */

            if ('jcr:primaryType' !== $property->getName()) {
                $name = $property->getName();
                $value = $this->objectManager->getNodeByIdentifier($property->getValue()->getIdentifier(), Version::class);
                $this->versionLabels[$name] = $value;
            }
        }
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function removeVersion($versionName): void
    {
        $version = $this->getVersion($versionName);

        $version->setCachedPredecessorsDirty();
        $version->setCachedSuccessorsDirty();

        $this->objectManager->removeVersion($this->getPath(), $versionName);

        if (isset($this->versions)) {
            unset($this->versions[$versionName]);
        }
    }

    /**
     * Tell the version history that it needs to reload, i.e. after a checkin operation.
     *
     * @private
     */
    public function notifyHistoryChanged(): void
    {
        unset($this->versions, $this->linearVersions, $this->versionLabels);
    }
}
