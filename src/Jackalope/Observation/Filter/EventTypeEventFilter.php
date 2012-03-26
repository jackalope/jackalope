<?php

namespace Jackalope\Observation\Filter;

use PHPCR\Observation\EventInterface;

class EventTypeEventFilter implements EventFilterInterface
{
    /**
     * @var int
     */
    protected $acceptedEventTypes;

    /**
     * @param int $acceptedEventType Accepted event types encoded as a bitmask
     */
    public function __construct($acceptedEventTypes)
    {
        $this->acceptedEventTypes = $acceptedEventTypes;
    }

    /**
     * {@inheritDoc}
     */
    public function match(EventInterface $event)
    {
        return ($this->acceptedEventTypes & $event->getType()) === $event->getType();
    }
}
