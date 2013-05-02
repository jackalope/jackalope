<?php

namespace Jackalope\Observation;

use Jackalope\TestCase;
use Jackalope\Factory;

use PHPCR\Observation\EventInterface;

/**
 * Unit tests for the EventJournal
 */
class EventJournalTest extends TestCase
{
    protected $factory;

    protected $journal;

    protected $session;

    protected $transport;

    public function setUp()
    {
        $this->session = $this->getSessionMock(array('getNode', 'getNodesByIdentifier'));
        $this->session
            ->expects($this->any())
            ->method('getNode')
            ->will($this->returnValue(null));
        $this->session
            ->expects($this->any())
            ->method('getNodesByIdentifier')
            ->will($this->returnValue(array()));
        $this->factory = new Factory();
        $this->journal = $this->getUnfilteredJournal(new \DOMDocument(), '');

        // XML for a single event
        $this->eventXml = <<<EOF
<event xmlns="http://www.day.com/jcr/webdav/1.0">
    <href xmlns="DAV:">http://localhost:8080/server/tests/jcr%3aroot/my_node%5b4%5d/jcr%3aprimaryType</href>
    <eventtype>
        <propertyadded/>
    </eventtype>
    <eventdate>1331652655099</eventdate>
    <eventuserdata>somedifferentdata</eventuserdata>
    <eventprimarynodetype>{http://www.jcp.org/jcr/nt/1.0}unstructured</eventprimarynodetype>
    <eventidentifier>8fe5b853-a657-4ee3-b626-ec3b5407dc13</eventidentifier>
</event>
EOF;

        // XML for event with eventinfo
        $this->eventWithInfoXml = <<<EOX
<event xmlns="http://www.day.com/jcr/webdav/1.0">
    <href
    xmlns="DAV:">http://localhost:8080/server/tests/jcr%3aroot/my_other</href>
    <eventtype>
        <nodemoved/>
    </eventtype>
    <eventdate>1332163767892</eventdate>
    <eventuserdata>somedifferentdata</eventuserdata>
    <eventprimarynodetype>{internal}root</eventprimarynodetype>
    <eventmixinnodetype>{internal}AccessControllable</eventmixinnodetype>
    <eventidentifier>1e80ac75-eff4-4350-bae6-7fae2a84e6f3</eventidentifier>
    <eventinfo>
        <destAbsPath>/my_other</destAbsPath>
        <srcAbsPath>/my_node</srcAbsPath>
    </eventinfo>
</event>
EOX;
        // XML for several events entries
        $this->entryXml = <<<EOF
<entry>
    <title>operations: /my_node[4]</title>
    <id>http://localhost/server/tests?type=journal?type=journal&amp;ts=1360caef7fb-0</id>
    <author>
        <name>system</name>
    </author>
    <updated>2012-03-13T16:30:55.099+01:00</updated>
    <content type="application/vnd.apache.jackrabbit.event+xml">
EOF;
        $this->entryXml .= $this->eventXml . "\n";
        $this->entryXml .= $this->eventXml . "\n"; // The same event appears twice in this entry
        $this->entryXml .= $this->eventWithInfoXml . "\n";
        $this->entryXml .= '</content></entry>';

        // The object representation of the event defined above
        $this->expectedEvent = new Event();
        $this->expectedEvent->setDate('1331652655');
        $this->expectedEvent->setIdentifier('8fe5b853-a657-4ee3-b626-ec3b5407dc13');
        $this->expectedEvent->setNodeType('{http://www.jcp.org/jcr/nt/1.0}unstructured');
        $this->expectedEvent->setPath('/my_node%5b4%5d/jcr%3aprimaryType');
        $this->expectedEvent->setType(EventInterface::PROPERTY_ADDED);
        $this->expectedEvent->setUserData('somedifferentdata');
        $this->expectedEvent->setUserId('system');

        $this->expectedEventWithInfo = new Event();
        $this->expectedEventWithInfo->setDate('1332163767');
        $this->expectedEventWithInfo->setIdentifier('1e80ac75-eff4-4350-bae6-7fae2a84e6f3');
        $this->expectedEventWithInfo->setNodeType('{internal}root');
        $this->expectedEventWithInfo->setPath('/my_other');
        $this->expectedEventWithInfo->setType(EventInterface::NODE_MOVED);
        $this->expectedEventWithInfo->setUserData('somedifferentdata');
        $this->expectedEventWithInfo->setUserId('system');
        $this->expectedEventWithInfo->addInfo('destAbsPath', '/my_other');
        $this->expectedEventWithInfo->addInfo('srcAbsPath', '/my_node');
    }

    public function testConstructor()
    {
        $filter = new EventFilter($this->factory, $this->session);
        $journal = new EventJournal($this->factory, $filter, $this->session, $this->transport);

        $this->myAssertAttributeEquals($this->factory, 'factory', $journal);
    }

    // ----- EXTRACT USER ID --------------------------------------------------

    public function testExtractUserId()
    {
        $xml = '<author><name>admin</name></author>';
        $res = $this->getAndCallMethod($this->journal, 'extractUserId', array($this->getDomElement($xml)));
        $this->assertEquals('admin', $res);

        $xml = '<author><name></name></author>';
        $res = $this->getAndCallMethod($this->journal, 'extractUserId', array($this->getDomElement($xml)));
        $this->assertEquals('', $res);
    }

    /**
     * @expectedException \PHPCR\RepositoryException
     */
    public function testExtractUserIdNoAuthor()
    {
        $xml = '<artist><name>admin</name></artist>';
        $this->getAndCallMethod($this->journal, 'extractUserId', array($this->getDomElement($xml)));
    }

    /**
     * @expectedException \PHPCR\RepositoryException
     */
    public function testExtractUserIdNoName()
    {
        $xml = '<author>admin</author>';
        $this->getAndCallMethod($this->journal, 'extractUserId', array($this->getDomElement($xml)));
    }

    public function testConstructEventJournal()
    {
        $filter = new EventFilter($this->factory, $this->session);

        $data = new \DOMDocument();
        $data->loadXML($this->entryXml);

        $this->transport = $this->getMock('\Jackalope\Transport\ObservationInterface');
        $this->transport
            ->expects($this->once())
            ->method('getEvents')
            ->with(0, $filter, $this->session)
            ->will($this->returnValue(array(
                'data' => $data,
                'stripPath' => 'http://localhost:8080/server/tests/jcr%3aroot',
                'nextMillis' => false,
            )))
        ;

        $journal = new EventJournal($this->factory, $filter, $this->session, $this->transport);

        $this->getAndCallMethod($journal, 'fetchJournal', array());
        $this->myAssertAttributeEquals(new \ArrayIterator(array($this->expectedEvent, $this->expectedEvent, $this->expectedEventWithInfo)), 'events', $journal);
    }

    public function testExtractEvents()
    {
        $filter = new EventFilter($this->factory, $this->session);
        $journal = new EventJournal($this->factory, $filter, $this->session, $this->transport);

        $this->setAttributeValue($journal, 'workspaceRootUri', 'http://localhost:8080/server/tests/jcr%3aroot');

        $events = $this->getAndCallMethod($journal, 'extractEvents', array($this->getDomElement($this->eventXml), 'system'));

        $this->assertEquals(array($this->expectedEvent), $events);
    }

    public function textExtractEventType()
    {
        $validEventTypes = array(
            'nodeadded' => EventInterface::NODE_ADDED,
            'nodemoved' => EventInterface::NODE_MOVED,
            'noderemoved' => EventInterface::NODE_REMOVED,
            'propertyadded' => EventInterface::PROPERTY_ADDED,
            'propertyremoved' => EventInterface::PROPERTY_REMOVED,
            'propertychanged' => EventInterface::PROPERTY_CHANGED,
            'persist' => EventInterface::PERSIST,
        );

        foreach ($validEventTypes as $string => $integer) {
            $xml = '<eventtype>' . $string . '</eventtype>';
            $res = $this->getAndCallMethod($this->journal, 'extractEventType', array($this->getDomElement($xml)));
            $this->assertEquals($integer, $res);
        }
    }

    /**
     * @expectedException \PHPCR\RepositoryException
     */
    public function textExtractEventTypeInvalidType()
    {
        $xml = '<eventtype><invalidType/></eventtype>';
        $this->getAndCallMethod($this->journal, 'extractEventType', array($this->getDomElement($xml)));
    }

    /**
     * @expectedException \PHPCR\RepositoryException
     */
    public function textExtractEventTypeNoType()
    {
        $xml = '<invalid><persist/></invalid>';
        $this->getAndCallMethod($this->journal, 'extractEventType', array($this->getDomElement($xml)));
    }

    /**
     * @expectedException \PHPCR\RepositoryException
     */
    public function textExtractEventTypeMalformed()
    {
        $xml = '<eventtype>some string</eventtype>';
        $this->getAndCallMethod($this->journal, 'extractEventType', array($this->getDomElement($xml)));
    }

    // ----- EVENTINFO ---------------------------------------------------

    public function testEventInfo()
    {
        $filter = new EventFilter($this->factory, $this->session);
        $journal = new EventJournal($this->factory, $filter, $this->session, $this->transport);
        $this->setAttributeValue($journal, 'workspaceRootUri', 'http://localhost:8080/server/tests/jcr%3aroot');

        $events = $this->getAndCallMethod($journal, 'extractEvents', array($this->getDomElement($this->eventWithInfoXml), 'system'));
        $eventWithInfo = $events[0];

        $eventInfo = $eventWithInfo->getInfo();
        $this->assertEquals($this->expectedEventWithInfo->getInfo(), $eventInfo);

        $expectedInfo = array(
            'destAbsPath' => '/my_other',
            'srcAbsPath' => '/my_node'
        );

        $this->assertEquals(count($expectedInfo), count($eventInfo));

        foreach ($expectedInfo as $key => $expectedValue) {
            $value = $eventInfo[$key];
            $this->assertSame($expectedValue, $value);
        }
    }

    public function testEmptyEventInfo()
    {
        $filter = new EventFilter($this->factory, $this->session);
        $journal = new EventJournal($this->factory, $filter, $this->session, $this->transport);
        $this->setAttributeValue($journal, 'workspaceRootUri', 'http://localhost:8080/server/tests/jcr%3aroot');

        // get an event that has no eventinfo
        $events = $this->getAndCallMethod($journal, 'extractEvents', array($this->getDomElement($this->eventXml), 'system'));
        $event = $events[0];
        $eventInfo = $event->getInfo();

        $this->assertInternalType('array', $eventInfo);
        $this->assertEquals(0, count($eventInfo));
    }

    public function testNoEndlessLoop()
    {
        $xml = $this->buildSkipToTestData();
        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        $journal = $this->getUnfilteredJournal($doc, 'http://localhost:8080/server/tests/jcr%3aroot', true);

        foreach($journal as $event) {
            // nothing
        }
        $this->assertFalse($journal->valid());
    }

    public function testSkipTo()
    {
        $filter = new EventFilter($this->factory, $this->session);
        $xml = $this->buildSkipToTestData();
        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        $filter = new EventFilter($this->factory, $this->session);

        $this->transport = $this->getMock('\Jackalope\Transport\ObservationInterface');
        $this->transport
            ->expects($this->exactly(2))
            ->method('getEvents')
            ->will($this->returnValue(array(
                'data' => $doc,
                'stripPath' => 'http://localhost:8080/server/tests/jcr%3aroot',
                'nextMillis' => false,
            )))
        ;

        $journal = new EventJournal($this->factory, $filter, $this->session, $this->transport);
        foreach($journal as $event) {
            // nothing
        }
        $this->assertFalse($journal->valid());

        $journal->skipTo(42);
        $this->assertTrue($journal->valid());
    }

    public function testIterator()
    {
        $xml = $this->buildSkipToTestData();
        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        $journal = $this->getUnfilteredJournal($doc, 'http://localhost:8080/server/tests/jcr%3aroot', true);
        $firstEvent = $journal->current();

        $journal->next();
        $journal->next();
        $this->assertNotSame($firstEvent, $journal->current());
        $journal->rewind();
        $this->assertSame($firstEvent, $journal->current());
    }

    // ----- PROTECTED METHODS ------------------------------------------------

    /**
     * Return an EventJournal instance without any filters set
     *
     * @param \DOMDocument $data
     * @param string $workspaceRootUri
     *
     * @return EventJournal
     */
    protected function getUnfilteredJournal(\DOMDocument $data, $workspaceRootUri, $once = false)
    {
        $filter = new EventFilter($this->factory, $this->session);

        $this->transport = $this->getMock('\Jackalope\Transport\ObservationInterface');
        $this->transport
            ->expects(($once ? $this->once() : $this->any()))
            ->method('getEvents')
            ->will($this->returnValue(array(
                'data' => $data,
                'stripPath' => $workspaceRootUri,
                'nextMillis' => false,
            )))
        ;
        return new EventJournal($this->factory, $filter, $this->session, $this->transport);
    }

    /**
     * Build the XML data to contruct an EventJournal to test the skipTo method
     * @return string
     */
    protected function buildSkipToTestData()
    {
        $event = <<<EOF
<event xmlns="http://www.day.com/jcr/webdav/1.0">
    <eventtype>
        <propertyadded/>
    </eventtype>
    <eventdate>{{DATE}}000</eventdate>
</event>
EOF;
        $entryHeader = <<<EOF
<entry>
    <author>
        <name>system</name>
    </author>
    <content type="application/vnd.apache.jackrabbit.event+xml">
EOF;

        $entryFooter = <<<EOF
    </content>
</entry>
EOF;
        $xml = $entryHeader;
        for ($i = 100; $i <= 500; $i += 10) {

            $xml .= str_replace('{{DATE}}', $i, $event);
        }
        $xml .= $entryFooter;

        return $xml;
    }

}
