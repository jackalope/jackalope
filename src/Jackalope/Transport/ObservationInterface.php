<?php

namespace Jackalope\Transport;

use PHPCR\SessionInterface;
use PHPCR\Observation\EventJournalInterface;
use PHPCR\Observation\EventFilterInterface;

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
     * Request a fragment of the observation journal from the server.
     *
     * This method returns a hashmap with 'data' containing the DOM of events,
     * 'nextMillis' the next timestamp if there are more events to be found,
     * false otherwise and 'stripPath' a path prefix to remove from the event
     * paths (can be empty string)
     *
     * The filter is passed in, but the transport is not required to respect
     * it. The EventJournal has to make sure it really does filter.
     *
     * @param int                  $date    milliseconds since the epoch - see
     *                                      EventJournalInterface::skipTo
     * @param EventFilterInterface $filter  event filter the transport may use
     * @param SessionInterface     $session in case the transport needs this
     *                                      for filtering
     *
     * @return array with keys data, nextMillis and stripPath
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
