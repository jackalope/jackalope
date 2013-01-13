<?php

namespace Jackalope\Observation;

use PHPCR\Observation\ObservationManagerInterface;
use PHPCR\Observation\EventListenerInterface;
use PHPCR\Observation\EventFilterInterface;
use PHPCR\SessionInterface;

use Jackalope\Transport\ObservationInterface;
use Jackalope\FactoryInterface;
use Jackalope\NotImplementedException;

/**
 * {@inheritDoc}
 *
 * Jackalope does not implement event listeners because we would need to poll Jackrabbit on
 * a regular basis to check if an event occured but there is nothing like threads in PHP.
 *
 * @api
 *
 * @author D. Barsotti <daniel.barsotti@liip.ch>
 */
class ObservationManager implements \IteratorAggregate, ObservationManagerInterface
{
    /**
     * @var FactoryInterface
     */
    protected $factory;

    /**
     * @var ObservationInterface
     */
    protected $transport;

    /**
     * @var SessionInterface
     */
    protected $session;


    public function __construct(FactoryInterface $factory, SessionInterface $session, ObservationInterface $transport)
    {
        $this->factory = $factory;
        $this->session = $session;
        $this->transport = $transport;
    }

    /**
     * {@inheritDoc}
     * @api
     */
    public function addEventListener(
        EventListenerInterface $listener,
        EventFilterInterface $filter
    )
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     * @api
     */
    public function removeEventListener(EventListenerInterface $listener)
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     * @api
     */
    public function getRegisteredEventListeners()
    {
        throw new NotImplementedException();
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
    public function getEventJournal(EventFilterInterface $filter)
    {
        return $this->transport->getEventJournal($this->session, $filter);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function createEventFilter()
    {
        return $this->factory->get(
            'Jackalope\\Observation\\EventFilter',
            array($this->session)
        );
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
