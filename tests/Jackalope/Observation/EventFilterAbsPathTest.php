<?php

namespace Jackalope\Observation;

use Jackalope\Observation\Event;

/**
 * Unit tests for the EventJournal
 */
class EventFilterAbsPathTest extends EventFilterTestCase
{
    public function testFilter()
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

    protected function assertFilterMatch(EventFilter $filter, $isSupposedToMatch, $path)
    {
        $event = new Event($this->factory, $this->getNodeTypeManager());
        $event->setPath($path);
        $this->assertEquals($isSupposedToMatch, $filter->match($event));
    }
}
