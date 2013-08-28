<?php

namespace Jackalope\Version;

use ArrayIterator;

use PHPCR\Version\VersionHistoryInterface;
use PHPCR\Version\VersionInterface;
use PHPCR\Version\VersionException;

use Jackalope\Node;
use Jackalope\ObjectManager;
use Jackalope\NotImplementedException;

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
class VersionHistory extends Node implements VersionHistoryInterface
{
    /**
     * Cache of all versions to only build the list once
     * @var array
     */
    protected $versions = null;

    /**
     * Cache of the linear versions to only build the list once
     * @var array
     */
    protected $linearVersions = null;

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getVersionableIdentifier()
    {
        return $this->getPropertyValue('jcr:versionableUuid');
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getRootVersion()
    {
        return $this->objectManager->getNode('jcr:rootVersion', $this->getPath(), 'Version\\Version');
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAllLinearVersions()
    {
        // OPTIMIZE: special iterator that delays loading the versions
        if (!$this->linearVersions) {
            $version = $this->getRootVersion();
            do {
                $this->linearVersions[$version->getName()] = $version;
            } while ($version = $version->getLinearSuccessor());
        }

        return new ArrayIterator($this->linearVersions);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAllVersions()
    {
        // OPTIMIZE: special iterator that delays loading the versions
        if (!$this->versions) {
            $rootVersion = $this->getRootVersion();
            $results = array($rootVersion->getName() => $rootVersion);
            $this->versions = array_merge($results, $this->getEventualSuccessors($rootVersion));
        }

        return new ArrayIterator($this->versions);
    }

    /**
     * Walk along the successors line to get all versions of this node
     *
     * According to spec, 3.13.1.4, these are called eventual successors
     *
     * @param VersionInterface $node the node to get successors
     *      from
     *
     * @return array list of VersionInterface
     */
    protected function getEventualSuccessors($node)
    {
        $successors = $node->getSuccessors();
        $results = array();
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
    public function getAllLinearFrozenNodes()
    {
        // OPTIMIZE: special iterator that delays loading frozen nodes
        $frozenNodes = array();
        foreach ($this->getAllLinearVersions() as $version) {
            $frozenNodes[$version->getName()] = $version->getFrozenNode();
        }

        return new ArrayIterator($frozenNodes);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAllFrozenNodes()
    {
        // OPTIMIZE: special iterator that delays loading frozen nodes
        $frozenNodes = array();
        foreach ($this->getAllVersions() as $version) {
            $frozenNodes[$version->getName()] = $version->getFrozenNode();
        }

        return new ArrayIterator($frozenNodes);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getVersion($versionName)
    {
        $this->getAllVersions();
        if (isset($this->versions[$versionName])) {
            return $this->versions[$versionName];
        }

        throw new VersionException("No version '$versionName'");
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getVersionByLabel($label)
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
    public function addVersionLabel($versionName, $label, $moveLabel)
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
    public function removeVersionLabel($label)
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
    public function hasVersionLabel($label, $version = null)
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
    public function getVersionLabels($version = null)
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
    public function removeVersion($versionName)
    {
        $version = $this->getVersion($versionName);

        $version->setCachedPredecessorsDirty();
        $version->setCachedSuccessorsDirty();

        $this->objectManager->removeVersion($this->getPath(), $versionName);

        if (!is_null($this->versions)) {
            unset($this->versions[$versionName]);
        }
    }

    /**
     * Tell the version history that it needs to reload, i.e. after a checkin operation
     *
     * @private
     */
    public function notifyHistoryChanged()
    {
        $this->versions = null;
        $this->linearVersions = null;
    }
}
