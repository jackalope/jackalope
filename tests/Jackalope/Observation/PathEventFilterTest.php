<?php

namespace Jackalope\Observation;

use Jackalope\TestCase,
    PHPCR\Observation\EventInterface,
    Jackalope\Observation\Event,
    Jackalope\Observation\Filter\PathEventFilter;


/**
 * Unit tests for the EventJournal
 */
class PathEventFilterTest extends TestCase
{
    public function testFilter()
    {
        $filter = new PathEventFilter('/somepath');
        $this->assertFilterMatch($filter, true, '/somepath');
        $this->assertFilterMatch($filter, false, '/somepath/child');
        $this->assertFilterMatch($filter, false, '/someotherpath');

        $filter = new PathEventFilter('/somepath', true);
        $this->assertFilterMatch($filter, true, '/somepath');
        $this->assertFilterMatch($filter, true, '/somepath/child');
        $this->assertFilterMatch($filter, false, '/someotherpath');
    }

    protected function assertFilterMatch(PathEventFilter $filter, $isSupposedToMatch, $path)
    {
        $event = new Event();
        $event->setPath($path);
        $this->assertEquals($isSupposedToMatch, $filter->match($event));
    }
}
