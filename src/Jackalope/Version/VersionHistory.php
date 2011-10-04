<?php

namespace Jackalope\Version;

use ArrayIterator;
use Jackalope\NotImplementedException;
use Jackalope\ObjectManager;

// inherit all doc
/**
 * @api
 */
class VersionHistory extends \Jackalope\Node
{
    protected $objectmanager; //TODO if we would use parent constructor, this would be present
    protected $path; //TODO if we would use parent constructor, this would be present

    /**
     * Cache of all versions to only fetch them once.
     * @var array
     */
    protected $versions = null;

    /**
     * FIXME: is this sane? we do not call the parent constructor
     */
    public function __construct($factory, ObjectManager $objectmanager,$absPath)
    {
        $this->objectmanager = $objectmanager;
        $this->path = $absPath;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getVersionableIdentifier()
    {
        throw new NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    public function getRootVersion()
    {
        throw new NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    public function getAllLinearVersions()
    {
        throw new NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    public function getAllVersions()
    {
        if (!$this->versions) {
            $uuid = $this->objectmanager->getVersionHistory($this->path);
            $node = $this->objectmanager->getNode($uuid, '/', 'Version\Version');
            $results = array();
            $rootNode = $this->objectmanager->getNode('jcr:rootVersion', $node->getPath(), 'Version\Version');
            $results[$rootNode->getName()] = $rootNode;
            $this->versions = array_merge($results, $this->getEventualSuccessors($rootNode));
        }
        return new ArrayIterator($this->versions);
    }

    /**
     * Walk along the successors line to get all versions of this node
     *
     * According to spec, 3.13.1.4, these are called eventual successors
     *
     * @param \PHPCR\Version\VersionInterface $node the node to get successors
     *      from
     *
     * @return array list of \PHPCR\VersionInterface
     */
    protected function getEventualSuccessors($node) {
        $successors = $node->getSuccessors();
        $results = array();
        foreach ($successors as $successor) {
            $results[$successor->getName()] = $successor;
            $results = array_merge($results, $this->getEventualSuccessors($successor)); //TODO: remove end recursion
        }
        return $results;
    }

    // inherit all doc
    /**
     * @api
     */
    public function getAllLinearFrozenNodes()
    {
        throw new NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    public function getAllFrozenNodes()
    {
        throw new NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    public function getVersion($versionName)
    {
        $this->getAllVersions();
        if (isset($this->versions[$versionName])) {
            return $this->versions[$versionName];
        }

        throw new \PHPCR\Version\VersionException("No version '$versionName'");
    }

    // inherit all doc
    /**
     * @api
     */
    public function getVersionByLabel($label)
    {
        throw new NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    public function addVersionLabel($versionName, $label, $moveLabel)
    {
        throw new NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    public function removeVersionLabel($label)
    {
        throw new NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    public function hasVersionLabel($label, $version = null)
    {
        throw new NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    public function getVersionLabels($version = null)
    {
        throw new NotImplementedException();
    }

    // inherit all doc
    /**
     * @api
     */
    public function removeVersion($versionName)
    {
        /*
         * this should send an immediate request with
         * DELETE /server/tests/jcr%3aroot/jcr%3asystem/jcr%3aversionStorage/88/f5/0e/88f50ee0-38fc-4cab-8ef1-706fb2f78cfe/1.4
         * ...
         * using the full path of the version node that is to be removed.
         */
        throw new NotImplementedException();
    }

}
