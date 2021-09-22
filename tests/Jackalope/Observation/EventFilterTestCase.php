<?php

namespace Jackalope\Observation;

use Jackalope\FactoryInterface;
use Jackalope\TestCase;
use PHPCR\SessionInterface;

/**
 * Unit tests for the EventFilter.
 */
abstract class EventFilterTestCase extends TestCase
{
    protected EventFilter $eventFilter;
    protected FactoryInterface $factory;
    protected SessionInterface $session;

    public function setUp(): void
    {
        $this->factory = $this->createMock(FactoryInterface::class);

        $this->session = $this->getSessionMock();
        $this->session
            ->method('getNodes')
            ->willReturn(
                []
            );

        $this->session
            ->method('getNodesByIdentifier')
            ->willReturn(
                []
            );

        $this->eventFilter = new EventFilter($this->factory, $this->session);
    }
}
