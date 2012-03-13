<?php

namespace Jackalope\Transport;

/**
 * Defines the methods needed for observation.
 *
 * @see <a href="http://www.day.com/specs/jcr/2.0/12_Observation.html">JCR 2.0, chapter 12</a>
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 */
interface ObservationInterface extends TransportInterface
{
    /**
     * Request the observation journal from the server
     *
     * @param integer $eventTypes A combination of one or more event type constants encoded as a bitmask.
     * @param string $absPath an absolute path.
     * @param boolean $isDeep Switch to define the given path as a reference to a child node.
     * @param array $uuid array of identifiers.
     * @param array $nodeTypeName array of node type names.
     * @return \PHPCR\Observation\EventJournalInterface an EventJournal (or null).
     *
     * @throws \PHPCR\RepositoryException if an error occurs
     */
    function getEventJournal($eventTypes = null, $absPath = null, $isDeep = null, array $uuid = null, array $nodeTypeName = null);
}
