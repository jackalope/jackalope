<?php

namespace Jackalope\Observation;

use PHPCR\Observation\EventInterface;
use Jackalope\Observation\Event;
use Jackalope\Observation\EventFilter;

/**
 * Unit tests for the EventFilter
 */
class EventFilterEventTypeTest extends EventFilterTestCase
{
    protected $allEventTypes = array(
        EventInterface::NODE_ADDED,
        EventInterface::NODE_MOVED,
        EventInterface::NODE_REMOVED,
        EventInterface::PERSIST,
        EventInterface::PROPERTY_ADDED,
        EventInterface::PROPERTY_CHANGED,
        EventInterface::PROPERTY_REMOVED
    );

    public function testNoMatchFilter()
    {
        foreach ($this->allEventTypes as $type) {
            $this->eventFilter->setEventTypes(0);
            $this->assertFilterMatch($this->eventFilter, array());
        }
    }

    public function testSingleTypeFilter()
    {
        foreach ($this->allEventTypes as $type) {
            $this->eventFilter->setEventTypes($type);
            $this->assertFilterMatch($this->eventFilter, array($type));
        }
    }

    public function testMultipleTypeFilter()
    {
        $this->assertMultiTypeFilter(array(EventInterface::NODE_REMOVED, EventInterface::PROPERTY_REMOVED));
        $this->assertMultiTypeFilter(array(EventInterface::PROPERTY_REMOVED, EventInterface::PROPERTY_ADDED, EventInterface::PROPERTY_CHANGED));
        $this->assertMultiTypeFilter(array(EventInterface::NODE_REMOVED, EventInterface::NODE_ADDED, EventInterface::NODE_MOVED, EventInterface::PERSIST));
    }

    /**
     * Create a filter accepting all the given $eventTypes, then assert it matches only those event types
     *
     * @param array $eventTypes The list of event types
     */
    protected function assertMultiTypeFilter($eventTypes)
    {
        $matchedTypes = 0;
        foreach ($eventTypes as $type) {
            $matchedTypes = $matchedTypes | $type;
        }

        $this->eventFilter->setEventTypes($matchedTypes);

        $this->assertFilterMatch($this->eventFilter, $eventTypes);
    }

    /**
     * Assert a filter only match the given event types
     *
     * @param EventFilter $filter
     * @param array       $matchedTypes An array of matched event types
     */
    protected function assertFilterMatch(EventFilter $filter, $matchedTypes)
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
