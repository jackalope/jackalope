<?php

namespace Jackalope\Observation;

use Jackalope\Observation\EventFilter;

/**
 * Unit tests for the EventFilter
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
        $this->eventFilter->setIdentifiers(array('1', '2', '3'));
        $this->assertEquals(array('1', '2', '3'), $this->eventFilter->getIdentifiers());
        $this->eventFilter->setNodeTypes(array('nodeType'));
        $this->assertEquals(array('nodeType'), $this->eventFilter->getNodeTypes());
    }

}
