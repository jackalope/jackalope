<?php

namespace Jackalope\Observation;

use Jackalope\TestCase;
use PHPCR\Observation\EventInterface;


/**
 * Unit tests for the EventJournal
 */
class EventJournalTest extends TestCase
{
    protected $factory;

    protected $journal;


    public function setUp()
    {
        $this->session = $this->getSessionMock();
        $this->factory = new \Jackalope\Factory();
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
    }

    public function testConstructor()
    {
        $eventTypes = 1234;
        $absPath = '/some/path';
        $isDeep = true;
        $uuid = array('1234', '4321');
        $nodeTypeName = array('type1', 'type2');
        $workspaceRootUri = 'http://some.workspace.uri/';

        $journal = new EventJournal($this->factory, $this->session, new \DOMDocument(), $eventTypes, $absPath, $isDeep, $uuid, $nodeTypeName, $workspaceRootUri);

        $this->myAssertAttributeEquals($this->factory, 'factory', $journal);
        $this->myAssertAttributeEquals($eventTypes, 'eventTypesCriterion', $journal);
        $this->myAssertAttributeEquals($isDeep, 'isDeepCriterion', $journal);
        $this->myAssertAttributeEquals($uuid, 'uuidCriterion', $journal);
        $this->myAssertAttributeEquals($nodeTypeName, 'nodeTypeNameCriterion', $journal);
        $this->myAssertAttributeEquals($workspaceRootUri, 'workspaceRootUri', $journal);
        $this->myAssertAttributeEquals(true, 'alreadyFiltered', $journal);
    }

    public function testContructorWithoutFilters()
    {
        // The journal contructed in setUp is unfiltered
        $this->myAssertAttributeEquals(false, 'alreadyFiltered', $this->journal);
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

    // ----- CONSTRUCT EVENT JOURNAL ------------------------------------------

    public function testConstructEventJournal()
    {
        $journal = new EventJournal($this->factory, $this->session, new \DOMDocument(), null, null, null, null, null, 'http://localhost:8080/server/tests/jcr%3aroot');

        $data = new \DOMDocument();
        $data->loadXML($this->entryXml);

        $events = $this->getAndCallMethod($journal, 'constructEventJournal', array($data));

        $this->assertEquals(array($this->expectedEvent, $this->expectedEvent), $events);
    }

    // ----- EXTRACT EVENTS ---------------------------------------------------

    public function testExtractEvents()
    {
        $journal = new EventJournal($this->factory, $this->session, new \DOMDocument(), null, null, null, null, null, 'http://localhost:8080/server/tests/jcr%3aroot');

        $events = $this->getAndCallMethod($journal, 'extractEvents', array($this->getDomElement($this->eventXml), 'system'));

        $this->assertEquals(array($this->expectedEvent), $events);
    }

    // ----- EXTRACT EVENT TYPE -----------------------------------------------

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

    // ----- SKIP TO ----------------------------------------------------------

    public function testSkipTo()
    {
        $xml = $this->buildSkipToTestData();
        $doc = new \DOMDocument();
        $doc->loadXML($xml);

        $journal = new EventJournal($this->factory, $this->session, $doc);
        $firstEvent = $journal->current();

        // ----- Skip to start of list

        $journal->skipTo(-1);
        $this->assertSame($firstEvent, $journal->current());

        $journal->skipTo(100);
        $this->assertSame($firstEvent, $journal->current());

        $journal->skipTo(110);
        $this->assertNotSame($firstEvent, $journal->current());
        $this->assertNotSame($firstEvent, $journal->current());

        // ----- Now skipping to an earlier date should not have any effect

        $journal->skipTo(100);
        $this->assertNotSame($firstEvent, $journal->current());

        // ----- Skip to end of list

        $journal->skipTo(490);
        $this->assertEquals(490, $journal->current()->getDate());

        $journal->next();
        $this->assertEquals(500, $journal->current()->getDate());

        $journal->next();
        $this->assertNull($journal->current());
    }

    // ----- PROTECTED METHODS ------------------------------------------------

    /**
     * Return an EventJournal instance without any filters set
     * @param \DOMDocument $data
     * @param string $workspaceRootUri
     * @return EventJournal
     */
    protected function getUnfilteredJournal(\DOMDocument $data, $workspaceRootUri)
    {
        return new EventJournal($this->factory, $this->session, $data, null, null, null, null, null, $workspaceRootUri);
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
