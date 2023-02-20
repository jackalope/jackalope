<?php

namespace Jackalope\Observation;

use Jackalope\FactoryInterface;
use Jackalope\NotImplementedException;
use Jackalope\Transport\ObservationInterface;
use PHPCR\Observation\EventFilterInterface;
use PHPCR\Observation\EventJournalInterface;
use PHPCR\Observation\EventListenerInterface;
use PHPCR\Observation\ObservationManagerInterface;
use PHPCR\RepositoryException;
use PHPCR\SessionInterface;

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
final class ObservationManager implements \IteratorAggregate, ObservationManagerInterface
{
    private FactoryInterface $factory;
    private ObservationInterface $transport;
    private SessionInterface $session;

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
    ): void {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function removeEventListener(EventListenerInterface $listener): void
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getRegisteredEventListeners(): \Iterator
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setUserData($userData): void
    {
        $this->transport->setUserData($userData);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getEventJournal(EventFilterInterface $filter): EventJournalInterface
    {
        return $this->factory->get(EventJournal::class, [$filter, $this->session, $this->transport]);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function createEventFilter(): EventFilterInterface
    {
        return $this->factory->get(EventFilter::class, [$this->session]);
    }

    /**
     * @return \Traversable The list of event listeners
     *
     * @throws RepositoryException
     *
     * @see getRegisteredEventListeners
     */
    public function getIterator(): \Traversable
    {
        return $this->getRegisteredEventListeners();
    }
}
