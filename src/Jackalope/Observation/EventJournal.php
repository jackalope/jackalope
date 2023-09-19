<?php

namespace Jackalope\Observation;

use Jackalope\FactoryInterface;
use Jackalope\Transport\ObservationInterface;
use PHPCR\Observation\EventInterface;
use PHPCR\Observation\EventJournalInterface;
use PHPCR\SessionInterface;

/**
 * @api
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 * @author David Buchmann <mail@davidbu.ch>
 * @author Daniel Barsotti <daniel.barsotti@liip.ch>
 */
final class EventJournal implements EventJournalInterface
{
    private SessionInterface $session;

    private EventFilter $filter;

    /**
     * Buffered events.
     */
    private ?\Iterator $events;

    private ObservationInterface $transport;

    /**
     * SkipTo timestamp for next fetch. Either manually set or next page.
     */
    private int $currentMillis;

    /**
     * Prepare a new EventJournal.
     *
     * Actual data loading is deferred to when it is first requested.
     *
     * @param EventFilter          $filter    filter to give the transport and
     *                                        apply locally
     * @param ObservationInterface $transport a transport implementing the
     *                                        observation methods
     */
    public function __construct(
        FactoryInterface $factory,
        EventFilter $filter,
        SessionInterface $session,
        ObservationInterface $transport
    ) {
        $this->filter = $filter;
        $this->session = $session;
        $this->transport = $transport;
        $this->skipTo(0);
    }

    /**
     * @api
     */
    public function skipTo($date): void
    {
        $this->currentMillis = $date;
        $this->events = null;
    }

    public function current(): ?EventInterface
    {
        if (!$this->events) {
            $this->fetchJournal();
        }

        return $this->events->current();
    }

    public function next(): void
    {
        if (!$this->events) {
            $this->fetchJournal();
        }

        $this->events->next();
    }

    public function key(): ?int
    {
        if (!$this->events) {
            $this->fetchJournal();
        }

        return $this->events->key();
    }

    public function valid(): bool
    {
        if (!$this->events) {
            $this->fetchJournal();
        }

        return $this->events->valid();
    }

    public function rewind(): void
    {
        if (!$this->events) {
            return;
        }

        $this->events->rewind();
    }

    public function seek($offset): void
    {
        $this->skipTo($offset);
    }

    private function fetchJournal(): void
    {
        $this->events = $this->transport->getEvents($this->currentMillis, $this->filter, $this->session);
    }
}
