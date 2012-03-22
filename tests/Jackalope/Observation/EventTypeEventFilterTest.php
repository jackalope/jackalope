<?php

namespace Jackalope\Observation;

use Jackalope\TestCase,
    PHPCR\Observation\EventInterface,
    Jackalope\Observation\Event,
    Jackalope\Observation\Filter\EventTypeEventFilter;


/**
 * Unit tests for the EventJournal
 */
class EventTypeEventFilterTest extends TestCase
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
            $this->assertFilterMatch(new EventTypeEventFilter(0), array());
        }
    }

    public function testSingleTypeFilter()
    {
        foreach ($this->allEventTypes as $type) {
            $this->assertFilterMatch(new EventTypeEventFilter($type), array($type));
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
     * @param array $eventTypes The list of event types
     * @return void
     */
    protected function assertMultiTypeFilter($eventTypes)
    {
        $matchedTypes = 0;
        foreach ($eventTypes as $type) {
            $matchedTypes = $matchedTypes | $type;
        }

        $filter = new EventTypeEventFilter($matchedTypes);

        $this->assertFilterMatch($filter, $eventTypes);
    }

    /**
     * Assert a filter only match the given event types
     * @param Filter\EventTypeEventFilter $filter
     * @param array $matchedTypes An array of matched event types
     * @return void
     */
    protected function assertFilterMatch(EventTypeEventFilter $filter, $matchedTypes)
    {
        foreach ($this->allEventTypes as $type) {
            $event = new Event();
            $event->setType($type);
            $mustAccept = in_array($type, $matchedTypes);
            $message = sprintf("The filter with accepted types '%s' did not match the event type '%s'", $this->getAttributeValue($filter, 'acceptedEventTypes'), $type);
            $this->assertEquals($mustAccept, $filter->match($event), $message);
        }
    }

}
