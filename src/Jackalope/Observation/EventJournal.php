<?php

namespace Jackalope\Observation;

use ArrayIterator;

use Jackalope\Transport\ObservationInterface;
use PHPCR\Observation\EventJournalInterface;
use PHPCR\SessionInterface;

use Jackalope\FactoryInterface;

/**
 * {@inheritDoc}
 *
 * @api
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @author David Buchmann <mail@davidbu.ch>
 * @author Daniel Barsotti <daniel.barsotti@liip.ch>
 */
class EventJournal implements EventJournalInterface
{
    /**
     * @var FactoryInterface
     */
    protected $factory;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var EventFilter
     */
    protected $filter;

    /**
     * Buffered events
     *
     * @var ArrayIterator
     */
    protected $events;

    /**
     * @var ObservationInterface
     */
    protected $transport;

    /**
     * SkipTo timestamp for next fetch. Either manually set or next page.
     *
     * @var int
     */
    protected $currentMillis;

    /**
     * The prefix to extract the path from the event href attribute
     *
     * @var string
     */
    protected $workspaceRootUri;

    /**
     * Prepare a new EventJournal.
     *
     * Actual data loading is deferred to when it is first requested.
     *
     * @param FactoryInterface     $factory
     * @param EventFilter          $filter    filter to give the transport and
     *                                        apply locally.
     * @param SessionInterface     $session
     * @param ObservationInterface $transport a transport implementing the
     *                                        observation methods.
     */
    public function __construct(FactoryInterface $factory, EventFilter $filter, SessionInterface $session, ObservationInterface $transport)
    {
        $this->factory = $factory;
        $this->filter = $filter;
        $this->session = $session;
        $this->transport = $transport;
        $this->skipTo(0);
    }

    /**
     * {@inheritDoc}
     * @api
     */
    public function skipTo($date)
    {
        $this->currentMillis = $date;
        $this->events = false;
    }

    public function current()
    {
        if (!$this->events) {
            $this->fetchJournal();
        }

        return $this->events->current();
    }

    public function next()
    {
        if (!$this->events) {
            $this->fetchJournal();
        }

        $this->events->next();
    }

    public function key()
    {
        if (!$this->events) {
            $this->fetchJournal();
        }

        return $this->events->key();
    }

    public function valid()
    {
        if (!$this->events) {
            $this->fetchJournal();
        }

        return $this->events->valid();
    }

    public function rewind()
    {
        if (!$this->events) {
            return;
        }

        $this->events->rewind();
    }

    public function seek($position)
    {
        $this->skipTo($position);
    }

    protected function fetchJournal()
    {
        $this->events = $this->transport->getEvents($this->currentMillis, $this->filter, $this->session);
    }
}
