<?php

namespace Jackalope\Observation;

use Jackalope\TestCase,
    PHPCR\Observation\EventInterface,
    Jackalope\Observation\Event,
    Jackalope\Observation\Filter,
    Jackalope\Observation\Filter\EventFilterChain;


/**
 * Unit tests for the EventJournal
 */
class EventFilterChainTest extends TestCase
{
    public function testFactory()
    {
        $session = $this->getSessionMock(array('getNodes', 'getNodesByIdentifier'));
        $session
            ->expects($this->any())
            ->method('getNodes')
            ->will($this->returnValue(null));
        $session
            ->expects($this->any())
            ->method('getNodesByIdentifier')
            ->will($this->returnValue(array()));

        $eventTypeFilter = new Filter\EventTypeEventFilter(123);
        $pathFilter = new Filter\PathEventFilter('/somepath', true);
        $uuidFilter = new Filter\UuidEventFilter($session, array('1', '2', '3'));
        $nodeTypeFilter = new Filter\NodeTypeEventFilter($session, array('nodeType'));

        $filter = EventFilterChain::constructFilterChain($session);
        $this->assertEquals(array(), $this->getAttributeValue($filter, 'filters'));

        $filter = EventFilterChain::constructFilterChain($session, 123);
        $this->assertEquals(array($eventTypeFilter), $this->getAttributeValue($filter, 'filters'));

        $filter = EventFilterChain::constructFilterChain($session, null, '/somepath', true);
        $this->assertEquals(array($pathFilter), $this->getAttributeValue($filter, 'filters'));

        $filter = EventFilterChain::constructFilterChain($session, null, null, null, array('1', '2', '3'));
        $this->assertEquals(array($uuidFilter), $this->getAttributeValue($filter, 'filters'));

        $filter = EventFilterChain::constructFilterChain($session, null, null, null, null, array('nodeType'));
        $this->assertEquals(array($nodeTypeFilter), $this->getAttributeValue($filter, 'filters'));

        $filter = EventFilterChain::constructFilterChain($session, 123, '/somepath', true, array('1', '2', '3'), array('nodeType'));
        $this->assertEquals(array($eventTypeFilter, $pathFilter, $uuidFilter, $nodeTypeFilter), $this->getAttributeValue($filter, 'filters'));
    }
}
