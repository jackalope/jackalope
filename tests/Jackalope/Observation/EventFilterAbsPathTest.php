<?php

namespace Jackalope\Observation;

/**
 * Unit tests for the EventJournal.
 */
final class EventFilterAbsPathTest extends EventFilterTestCase
{
    public function testFilter(): void
    {
        $this->eventFilter->setAbsPath('/somepath');
        $this->assertFilterMatch($this->eventFilter, true, '/somepath');
        $this->assertFilterMatch($this->eventFilter, false, '/somepath/child');
        $this->assertFilterMatch($this->eventFilter, false, '/someotherpath');

        $this->eventFilter->setAbsPath('/somepath');
        $this->eventFilter->setIsDeep(true);
        $this->assertFilterMatch($this->eventFilter, true, '/somepath');
        $this->assertFilterMatch($this->eventFilter, true, '/somepath/child');
        $this->assertFilterMatch($this->eventFilter, false, '/someotherpath');
    }

    protected function assertFilterMatch(EventFilter $filter, $isSupposedToMatch, $path): void
    {
        $event = new Event($this->factory, $this->getNodeTypeManager());
        $event->setPath($path);
        $this->assertEquals($isSupposedToMatch, $filter->match($event));
    }
}
