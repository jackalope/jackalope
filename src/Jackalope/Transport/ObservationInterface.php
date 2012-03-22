<?php

namespace Jackalope\Transport;

use PHPCR\SessionInterface;


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
     * When getting the journal we need to pass the session to the returned EventJournal
     * in order for it to be able to do filtering on the events if the backend didn't.
     * Indeed some events filters criteria involve checking the parent node of the node
     * issuing the event. The only way to do so is to use the session.
     *
     * @param \PHPCR\SessionInterface $session
     * @param integer $eventTypes A combination of one or more event type constants encoded as a bitmask.
     * @param string $absPath an absolute path.
     * @param boolean $isDeep Switch to define the given path as a reference to a child node.
     * @param array $uuid array of identifiers.
     * @param array $nodeTypeName array of node type names.
     * @return \PHPCR\Observation\EventJournalInterface an EventJournal (or null).
     *
     * @throws \PHPCR\RepositoryException if an error occurs
     */
    function getEventJournal(SessionInterface $session, $eventTypes = null, $absPath = null, $isDeep = null, array $uuid = null, array $nodeTypeName = null);


    /**
     * Set user data to be included with subsequent requests.
     * Setting userData to null (which it is by default) will result in no user data header being sent.
     *
     * @param mixed $userData null or string
     */
    function setUserData($userData);
}
