<?php

namespace Jackalope\Observation;

/**
 * Unit tests for the EventJournal
 */
class EventFilterIdentifiersTest extends EventFilterTestCase
{
    public function testFilter()
    {
        $this->setFilters(array('1', '2', '3'));
        $this->assertTrue($this->eventFilter->match($this->getEvent('/1/2', '1')));
        $this->assertTrue($this->eventFilter->match($this->getEvent('/2/2', '2')));
        $this->assertTrue($this->eventFilter->match($this->getEvent('/3/2', '3')));
        $this->assertFalse($this->eventFilter->match($this->getEvent('/1/2/3', '4')));
        $this->assertFalse($this->eventFilter->match($this->getEvent('/4/1', null)));
    }

    public function testNoMatchFilter()
    {
        $this->eventFilter->setIdentifiers(array());

        $nodes = array($this->getMyNodeMock('1'));
        $this->session
            ->expects($this->any())
            ->method('getNodesByIdentifier')
            ->will($this->returnValue($nodes)
        );

        $this->assertFalse($this->eventFilter->match($this->getEvent('/1/2', '1')));
        $this->assertFalse($this->eventFilter->match($this->getEvent('/2/2', '2')));
        $this->assertFalse($this->eventFilter->match($this->getEvent('/3/2', null)));
    }

    /**
     * Get an Event with the given path
     * @param  string $path
     * @return Event
     */
    protected function getEvent($path, $id)
    {
        $event = new Event($this->factory, $this->getNodeTypeManager());
        $event->setPath($path);
        $event->setIdentifier($id);

        return $event;
    }

    /**
     * Set eventFilter to the identifiers
     *
     * Additionally set the session getNodesByIdentifier to return an array of nodes
     * that will match the requirements of the filter
     *
     * @param string[] $identifiers
     */
    protected function setFilters($identifiers)
    {
        $nodes = array();
        foreach ($identifiers as $uuid) {
            $nodes[] = $this->getMyNodeMock($uuid);
        }
        $this->session
            ->expects($this->any())
            ->method('getNodesByIdentifier')
            ->will($this->returnValue($nodes)
        );
        $this->eventFilter->setIdentifiers($identifiers);
    }

    /**
     * Get a Jackalope\Node mock object that will return "/uuid" as path
     * @param  string $uuid
     * @return object
     */
    protected function getMyNodeMock($uuid)
    {
        $node = $this->getNodeMock();
        $node
            ->expects($this->any())
            ->method('getPath')
            ->will($this->returnValue('/' . $uuid))
        ;

        return $node;
    }
}
