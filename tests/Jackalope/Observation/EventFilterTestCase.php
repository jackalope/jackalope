<?php

namespace Jackalope\Observation;

use Jackalope\FactoryInterface;
use Jackalope\TestCase;
use PHPCR\SessionInterface;

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
     * @var FactoryInterface
     */
    protected $factory;

    /**
     * @var SessionInterface
     */
    protected $session;

    public function setUp()
    {
        $this->factory = $this->createMock(FactoryInterface::class);

        $this->session = $this->getSessionMock();
        $this->session
            ->expects($this->any())
            ->method('getNodes')
            ->will(
                $this->returnValue([])
            );

        $this->session
            ->expects($this->any())
            ->method('getNodesByIdentifier')
            ->will(
                $this->returnValue([])
            );

        $this->eventFilter = new EventFilter($this->factory, $this->session);
    }
}
