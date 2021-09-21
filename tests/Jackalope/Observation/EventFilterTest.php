<?php

namespace Jackalope\Observation;

/**
 * Unit tests for the EventFilter.
 */
class EventFilterTest extends EventFilterTestCase
{
    public function testContainer()
    {
        $this->eventFilter->setEventTypes(123);
        $this->assertEquals(123, $this->eventFilter->getEventTypes());
        $this->eventFilter->setAbsPath('/somepath');
        $this->assertEquals('/somepath', $this->eventFilter->getAbsPath());
        $this->eventFilter->setIsDeep(true);
        $this->assertTrue($this->eventFilter->getIsDeep());
        $this->eventFilter->setIdentifiers(['1', '2', '3']);
        $this->assertEquals(['1', '2', '3'], $this->eventFilter->getIdentifiers());
        $this->eventFilter->setNodeTypes(['nodeType']);
        $this->assertEquals(['nodeType'], $this->eventFilter->getNodeTypes());
    }
}
