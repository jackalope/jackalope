<?php

namespace Jackalope\Observation;

use Jackalope\TestCase;
use Jackalope\Observation\EventFilter;

/**
 * Unit tests for the EventFilter
 */
abstract class EventFilterTestCase extends TestCase
{
    /**
     * @var EventFilter
     */
    protected $eventFilter;
    /**
     * @var \Jackalope\FactoryInterface
     */
    protected $factory;
    /**
     * @var \PHPCR\SessionInterface
     */
    protected $session;

    public function setUp()
    {
        $this->factory = $this->getMock('Jackalope\\FactoryInterface');
        $this->session = $this->getSessionMock();
        $this->session
            ->expects($this->any())
            ->method('getNodes')
            ->will($this->returnValue(array())
        );
        $this->session
            ->expects($this->any())
            ->method('getNodesByIdentifier')
            ->will($this->returnValue(array())
        );

        $this->eventFilter = new EventFilter($this->factory, $this->session);
    }
}
