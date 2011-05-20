<?php

declare(ENCODING = 'utf-8');

namespace Jackalope\Version;

use Jackalope\NotImplementedException;
use Jackalope\Node;

class Version extends Node implements \PHPCR\Version\VersionInterface {

    protected $objectmanager;

    public function  __construct($factory, $rawData, $path, $session, $objectManager, $new = false) {
        $this->objectmanager = $objectManager;
        parent::__construct($factory, $rawData, $path, $session, $objectManager, $new);
    }
    /**
     * Returns the VersionHistory that contains this Version
     *
     * @return \PHPCR\Version\VersionHistoryInterface the VersionHistory that contains this Version
     * @throws \PHPCR\RepositoryException if an error occurs
     * @api
     */
    public function getContainingHistory()
    {
       throw new NotImplementedException();
    }

    /**
     * Returns the date this version was created. This corresponds to the
     * value of the jcr:created property in the nt:version node that represents
     * this version.
     *
     * @return \DateTime a \DateTime object
     * @throws \PHPCR\RepositoryException - if an error occurs
     * @api
     */
    public function getCreated()
    {
        throw new NotImplementedException();
    }

    /**
     * Assuming that this Version object was acquired through a Workspace W and
     * is within the VersionHistory H, this method returns the successor of this
     * version along the same line of descent as is returned by
     * H.getAllLinearVersions() where H was also acquired through W.
     *
     * Note that under simple versioning the behavior of this method is equivalent
     * to getting the unique successor (if any) of this version.
     *
     * @return \PHPCR\VersionInterface a Version or null if no linear successor exists.
     * @throws \PHPCR\RepositoryException if an error occurs.
     * @see VersionHistory::getAllLinearVersions()
     * @api
     */
    public function getLinearSuccessor()
    {
        throw new NotImplementedException();
    }


    /**
     * Returns the successor versions of this version. This corresponds to
     * returning all the nt:version nodes referenced by the jcr:successors
     * multi-value property in the nt:version node that represents this version.
     *
     * @return array of \PHPCR\Version\VersionInterface
     * @throws \PHPCR\RepositoryException if an error occurs
     * @api
     */
    public function getSuccessors()
    {
        if (! $this->hasProperty('jcr:successors')) {
            // no successors
            return array();
        }

        /* predecessors is a multivalue property with REFERENCE.
         * get it as string so we can create the Version instances from uuid
         * with the objectmanager
         */
        $successors = $this->getProperty("jcr:successors")->getString();
        $results = array();
        if ($successors) {
            foreach ($successors as $uuid) {
                $n = $this->objectmanager->getNode($uuid, '/', 'Version\Version');
                $results[] = $n;
            }
        }
        return $results;

    }


    /**
     * Assuming that this Version object was acquired through a Workspace W and
     * is within the VersionHistory H, this method returns the predecessor of
     * this version along the same line of descent as is returned by
     * H.getAllLinearVersions() where H was also acquired through W.
     *
     * Note that under simple versioning the behavior of this method is equivalent
     * to getting the unique predecessor (if any) of this version.
     *
     * @return \PHPCR\Version\VersionInterface a Version or null if no linear predecessor exists.
     * @throws \PHPCR\RepositoryException if an error occurs.
     * @see VersionHistory::getAllLinearVersions()
     * @api
     */
    public function getLinearPredecessor()
    {
        throw new NotImplementedException();
    }


    /**
     * In both simple and full versioning repositories, this method returns the
     * predecessor versions of this version. This corresponds to returning all
     * the nt:version nodes whose jcr:successors property includes a reference
     * to the nt:version node that represents this version.
     *
     * @return array of \PHPCR\Version\VersionInterface
     * @throws \PHPCR\RepositoryException if an error occurs
     * @api
     */
    public function getPredecessors()
    {
        if (! $this->hasProperty('jcr:predecessors')) {
            // no predecessors
            return array();
        }

        /*
         * predecessors is a multivalue property with REFERENCE.
         * get it as string so we can create the Version instances from uuid
         * with the objectmanager. see 3.13.2.6
         */
        $predecessors = $this->getProperty("jcr:predecessors")->getString();
        $results = array();
        foreach ($predecessors as $uuid) {
            $results[] = $this->objectmanager->getNode($uuid, '/', 'Version\Version');
        }
        return $results;
    }


    /**
     * Returns the frozen node of this version.
     *
     * @return \PHPCR\NodeInterface a Node object
     * @throws \PHPCR\RepositoryException if an error occurs
     * @api
     */
    public function getFrozenNode()
    {
        $frozen = $this->getNode('jcr:frozenNode');
        //TODO: what should we do now? recreate the node with the data at that time?
        throw new NotImplementedException();
    }
}
