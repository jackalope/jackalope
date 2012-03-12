<?php

namespace Jackalope\Observation;

use PHPCR\Observation\ObservationManagerInterface,
    PHPCR\Observation\EventListenerInterface;

/**
 * {@inheritDoc}
 *
 * @api
 *
 * @author D. Barsotti <daniel.barsotti@liip.ch>
 */
class ObservationManager implements ObservationManagerInterface
{

    /**
     * {@inheritDoc}
     * @api
     */
    function addEventListener(
        EventListenerInterface $listener,
        $eventTypes,
        $absPath,
        $isDeep,
        array $uuid,
        array $nodeTypeName, $noLocal
    )
    {
        throw new \Jackalope\NotImplementedException();
    }

    /**
     * {@inheritDoc}
     * @api
     */
    function removeEventListener(EventListenerInterface $listener)
    {
        throw new \Jackalope\NotImplementedException();
    }

    /**
     * {@inheritDoc}
     * @api
     */
    function getRegisteredEventListeners()
    {
        throw new \Jackalope\NotImplementedException();
    }

    /**
     * {@inheritDoc}
     * @api
     */
    function setUserData($userData)
    {
        throw new \Jackalope\NotImplementedException();
    }

    /**
     * {@inheritDoc}
     * @api
     */
    function getEventJournal(
        $eventTypes = null,
        $absPath = null,
        $isDeep = null,
        array $uuid = null,
        array $nodeTypeName = null
    )
    {
        throw new \Jackalope\NotImplementedException();
    }
}
