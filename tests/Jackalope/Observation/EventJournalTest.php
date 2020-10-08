<?php

namespace Jackalope\Observation;

use ArrayIterator;
use Jackalope\FactoryInterface;
use Jackalope\TestCase;
use Jackalope\Factory;
use Jackalope\Transport\TransportInterface;
use PHPCR\Observation\EventJournalInterface;
use PHPCR\SessionInterface;
use Jackalope\Transport\ObservationInterface;

/**
 * Unit tests for the EventJournal
 */
class EventJournalTest extends TestCase
{
    /**
     * @var FactoryInterface
     */
    protected $factory;

    /**
     * @var EventJournalInterface
     */
    protected $journal;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var TransportInterface
     */
    protected $transport;

    public function setUp(): void
    {
        $this->session = $this->getSessionMock(['getNode', 'getNodesByIdentifier']);
        $this->session
            ->expects($this->any())
            ->method('getNode')
            ->will($this->returnValue(null));
        $this->session
            ->expects($this->any())
            ->method('getNodesByIdentifier')
            ->will($this->returnValue([]));
        $this->factory = new Factory();

        $this->transport = $this->createMock(ObservationInterface::class);
    }

    public function testConstructor()
    {
        $this->transport
            ->expects($this->never())
            ->method('getEvents')
        ;
        $filter = new EventFilter($this->factory, $this->session);
        $journal = new EventJournal($this->factory, $filter, $this->session, $this->transport);

        $this->myAssertAttributeEquals($this->factory, 'factory', $journal);
    }

    public function testFetchBuffer()
    {
        $filter = new EventFilter($this->factory, $this->session);

        $this->transport
            ->expects($this->once())
            ->method('getEvents')
            ->with(0, $filter, $this->session)
            ->will($this->returnValue('test'))
        ;

        $journal = new EventJournal($this->factory, $filter, $this->session, $this->transport);

        $this->getAndCallMethod($journal, 'fetchJournal', []);
        $this->myAssertAttributeEquals('test', 'events', $journal);
    }

    public function testSkipTo()
    {
        $filter = new EventFilter($this->factory, $this->session);

        $this->transport
            ->expects($this->once())
            ->method('getEvents')
            ->with(2, $filter, $this->session)
            ->will($this->returnValue('test-data'))
        ;

        $journal = new EventJournal($this->factory, $filter, $this->session, $this->transport);
        $journal->skipTo(2);

        $this->getAndCallMethod($journal, 'fetchJournal', []);
        $this->myAssertAttributeEquals('test-data', 'events', $journal);
    }

    public function testIterator()
    {
        $filter = new EventFilter($this->factory, $this->session);

        $event1 = new Event($this->factory, $this->getNodeTypeManager());
        $event1->setDate(2);
        $event2 = new Event($this->factory, $this->getNodeTypeManager());
        $event2->setDate(3);

        $this->transport
            ->expects($this->once())
            ->method('getEvents')
            ->with(2, $filter, $this->session)
            ->will($this->returnValue(
                new ArrayIterator([$event1, $event2])
            ))
        ;

        $journal = new EventJournal($this->factory, $filter, $this->session, $this->transport);
        $journal->skipTo(2);

        $this->assertTrue($journal->valid());
        $this->assertSame($event1, $journal->current());

        $journal->next();
        $this->assertTrue($journal->valid());
        $this->assertSame($event2, $journal->current());

        $journal->next();
        $this->assertFalse($journal->valid());

        $journal->rewind();
        $this->assertTrue($journal->valid());
        $this->assertSame($event1, $journal->current());
    }
}
