<?php

namespace Jackalope\Observation;

use PHPCR\Observation\EventInterface;

/**
 * Unit tests for the EventFilter.
 */
final class EventFilterEventTypeTest extends EventFilterTestCase
{
    private array $allEventTypes = [
        EventInterface::NODE_ADDED,
        EventInterface::NODE_MOVED,
        EventInterface::NODE_REMOVED,
        EventInterface::PERSIST,
        EventInterface::PROPERTY_ADDED,
        EventInterface::PROPERTY_CHANGED,
        EventInterface::PROPERTY_REMOVED,
    ];

    public function testNoMatchFilter(): void
    {
        foreach ($this->allEventTypes as $type) {
            $this->eventFilter->setEventTypes(0);
            $this->assertFilterMatch($this->eventFilter, []);
        }
    }

    public function testSingleTypeFilter(): void
    {
        foreach ($this->allEventTypes as $type) {
            $this->eventFilter->setEventTypes($type);
            $this->assertFilterMatch($this->eventFilter, [$type]);
        }
    }

    public function testMultipleTypeFilter(): void
    {
        $this->assertMultiTypeFilter([EventInterface::NODE_REMOVED, EventInterface::PROPERTY_REMOVED]);
        $this->assertMultiTypeFilter([EventInterface::PROPERTY_REMOVED, EventInterface::PROPERTY_ADDED, EventInterface::PROPERTY_CHANGED]);
        $this->assertMultiTypeFilter([EventInterface::NODE_REMOVED, EventInterface::NODE_ADDED, EventInterface::NODE_MOVED, EventInterface::PERSIST]);
    }

    /**
     * Create a filter accepting all the given $eventTypes, then assert it matches only those event types.
     *
     * @param array $eventTypes The list of event types
     */
    protected function assertMultiTypeFilter($eventTypes): void
    {
        $matchedTypes = 0;
        foreach ($eventTypes as $type) {
            $matchedTypes |= $type;
        }

        $this->eventFilter->setEventTypes($matchedTypes);

        $this->assertFilterMatch($this->eventFilter, $eventTypes);
    }

    /**
     * Assert a filter only match the given event types.
     *
     * @param array $matchedTypes An array of matched event types
     */
    protected function assertFilterMatch(EventFilter $filter, $matchedTypes): void
    {
        foreach ($this->allEventTypes as $type) {
            $event = new Event($this->factory, $this->getNodeTypeManager());
            $event->setType($type);
            $mustAccept = in_array($type, $matchedTypes);
            $message = sprintf("The filter with accepted types '%s' did not match the event type '%s'", $this->eventFilter->getEventTypes(), $type);
            $this->assertEquals($mustAccept, $filter->match($event), $message);
        }
    }
}
