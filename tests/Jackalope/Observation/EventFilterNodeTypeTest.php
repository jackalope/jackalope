<?php

namespace Jackalope\Observation;

use PHPCR\PathNotFoundException;

/**
 * Unit tests for the EventFilter node type.
 */
final class EventFilterNodeTypeTest extends EventFilterTestCase
{
    public function testFilterFind(): void
    {
        $node = $this->getNodeMock();
        $node
            ->method('isNodeType')
            ->with('nt:unstructured')
            ->willReturn(true);

        $this->session
            ->method('getItem')
            ->with('/some/path')
            ->willReturn($node)
        ;

        $this->eventFilter->setNodeTypes(['nt:unstructured']);
        $this->assertFilterMatch($this->eventFilter, true);
    }

    public function testFilterFindNotType(): void
    {
        $node = $this->getNodeMock();
        $node
            ->method('isNodeType')
            ->with('nt:unstructured')
            ->willReturn(false);

        $this->session
            ->method('getItem')
            ->with('/some/path')
            ->willReturn($node)
        ;

        $this->eventFilter->setNodeTypes(['nt:unstructured']);
        $this->assertFilterMatch($this->eventFilter, false);
    }

    public function testFilterNofind(): void
    {
        $this->session
            ->method('getItem')
            ->with('/some/path')
            ->will($this->throwException(new PathNotFoundException()))
        ;
        $this->eventFilter->setNodeTypes(['nt:unstructured']);
        $this->assertFilterMatch($this->eventFilter, false);
    }

    protected function assertFilterMatch(EventFilter $filter, $expectedResult): void
    {
        $event = new Event($this->factory, $this->getNodeTypeManager());
        $event->setPath('/some/path');
        $this->assertEquals($expectedResult, $filter->match($event));
    }
}
