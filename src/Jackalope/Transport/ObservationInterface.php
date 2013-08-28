<?php

namespace Jackalope\Transport;

use Iterator;
use PHPCR\SessionInterface;
use PHPCR\Observation\EventFilterInterface;

/**
 * Defines the methods needed for observation.
 *
 * @see <a href="http://www.day.com/specs/jcr/2.0/12_Observation.html">JCR 2.0, chapter 12</a>
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
interface ObservationInterface extends TransportInterface
{
    /**
     * Request a fragment of the observation journal from the server.
     *
     * This method returns a buffer of events matching the filter that might
     * lazy-load events from storage. But it may never load events that happen
     * later than the time the buffer was created, to avoid endless looping on
     * busy repositories.
     *
     * @param int                  $date    milliseconds since the epoch - see
     *                                      EventJournalInterface::skipTo
     * @param EventFilterInterface $filter  event filter the transport must apply
     * @param SessionInterface     $session in case the transport needs this
     *                                      for filtering
     *
     * @return Iterator
     *
     * @throws \PHPCR\RepositoryException if an error occurs
     */
    public function getEvents($date, EventFilterInterface $filter, SessionInterface $session);

    /**
     * Set user data to be included with subsequent requests.
     * Setting userData to null (which it is by default) will result in no user data header being sent.
     *
     * @param mixed $userData null or string
     */
    public function setUserData($userData);
}
