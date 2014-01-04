<?php

namespace Jackalope\Observation;

use Jackalope\Observation\Event;
use Jackalope\Observation\EventFilter;

/**
 * Unit tests for the EventFilter node type
 */
class EventFilterNodeTypeTest extends EventFilterTestCase
{
    public function testFilterFind()
    {
        $node = $this->getNodeMock();
        $node
            ->expects($this->any())
            ->method('isNodeType')
            ->with('nt:unstructured')
            ->will($this->returnValue(true));

        $this->session
            ->expects($this->any())
            ->method('getItem')
            ->with('/some/path')
            ->will($this->returnValue($node))
        ;

        $this->eventFilter->setNodeTypes(array('nt:unstructured'));
        $this->assertFilterMatch($this->eventFilter, true);

    }

    public function testFilterFindNotType()
    {
        $node = $this->getNodeMock();
        $node
            ->expects($this->any())
            ->method('isNodeType')
            ->with('nt:unstructured')
            ->will($this->returnValue(false));

        $this->session
            ->expects($this->any())
            ->method('getItem')
            ->with('/some/path')
            ->will($this->returnValue($node))
        ;

        $this->eventFilter->setNodeTypes(array('nt:unstructured'));
        $this->assertFilterMatch($this->eventFilter, false);

    }

    public function testFilterNofind()
    {
        $this->session
            ->expects($this->any())
            ->method('getItem')
            ->with('/some/path')
            ->will($this->throwException(new \PHPCR\PathNotFoundException()))
        ;
        $this->eventFilter->setNodeTypes(array('nt:unstructured'));
        $this->assertFilterMatch($this->eventFilter, false);
    }

    protected function assertFilterMatch(EventFilter $filter, $expectedResult)
    {
        $event = new Event($this->factory, $this->getNodeTypeManager());
        $event->setPath('/some/path');
        $this->assertEquals($expectedResult, $filter->match($event));
    }

}
