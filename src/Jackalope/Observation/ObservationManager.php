<?php

namespace Jackalope\Observation;

use PHPCR\Observation\ObservationManagerInterface,
    PHPCR\Observation\EventListenerInterface,
    PHPCR\SessionInterface;

use Jackalope\Transport\ObservationInterface,
    Jackalope\FactoryInterface;



/**
 * {@inheritDoc}
 *
 * @api
 *
 * @author D. Barsotti <daniel.barsotti@liip.ch>
 */
class ObservationManager implements \IteratorAggregate, ObservationManagerInterface
{
    /**
     * @var \Jackalope\Transport\ObservationInterface
     */
    protected $transport;

    /**
     * @var \PHPCR\SessionInterface
     */
    protected $session;


    public function __construct(FactoryInterface $factory, SessionInterface $session, ObservationInterface $transport)
    {
        $this->transport = $transport;
        $this->session = $session;
    }

    /**
     * {@inheritDoc}
     * @api
     */
    public function addEventListener(
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
    public function removeEventListener(EventListenerInterface $listener)
    {
        throw new \Jackalope\NotImplementedException();
    }

    /**
     * {@inheritDoc}
     * @api
     */
    public function getRegisteredEventListeners()
    {
        throw new \Jackalope\NotImplementedException();
    }

    /**
     * {@inheritDoc}
     * @api
     */
    public function setUserData($userData)
    {
        $this->transport->setUserData($userData);
    }

    /**
     * {@inheritDoc}
     * @api
     */
    public function getEventJournal($eventTypes = null, $absPath = null, $isDeep = null, array $uuid = null, array $nodeTypeName = null)
    {
        return $this->transport->getEventJournal($this->session, $eventTypes, $absPath, $isDeep, $uuid, $nodeTypeName);
    }

    /**
     * @return \Traversable The list of event listeners
     * @see getRegisteredEventListeners
     */
    public function getIterator()
    {
        return $this->getRegisteredEventListeners();
    }
}
