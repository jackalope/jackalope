<?php

namespace Jackalope\Observation;

use IteratorAggregate;

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
 * a regular basis to check if an event occurred but there is nothing like threads in PHP.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @api
 *
 * @author D. Barsotti <daniel.barsotti@liip.ch>
 */
class ObservationManager implements IteratorAggregate, ObservationManagerInterface
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
     *
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
     *
     * @api
     */
    public function removeEventListener(EventListenerInterface $listener)
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getRegisteredEventListeners()
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setUserData($userData)
    {
        $this->transport->setUserData($userData);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getEventJournal(EventFilterInterface $filter)
    {
        return $this->factory->get(
            'Observation\\EventJournal',
            array($filter, $this->session, $this->transport)
        );
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
     *
     * @see getRegisteredEventListeners
     */
    public function getIterator()
    {
        return $this->getRegisteredEventListeners();
    }
}
