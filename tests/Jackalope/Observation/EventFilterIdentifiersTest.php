<?php

namespace Jackalope\Observation;

/**
 * Unit tests for the EventJournal.
 */
class EventFilterIdentifiersTest extends EventFilterTestCase
{
    public function testFilter(): void
    {
        $this->setFilters(['1', '2', '3']);
        $this->assertTrue($this->eventFilter->match($this->getEvent('/1/2', '1')));
        $this->assertTrue($this->eventFilter->match($this->getEvent('/2/2', '2')));
        $this->assertTrue($this->eventFilter->match($this->getEvent('/3/2', '3')));
        $this->assertFalse($this->eventFilter->match($this->getEvent('/1/2/3', '4')));
        $this->assertFalse($this->eventFilter->match($this->getEvent('/4/1', null)));
    }

    public function testNoMatchFilter(): void
    {
        $this->eventFilter->setIdentifiers([]);

        $nodes = [$this->getMyNodeMock('1')];
        $this->session
            ->method('getNodesByIdentifier')
            ->willReturn(
                $nodes
            );

        $this->assertFalse($this->eventFilter->match($this->getEvent('/1/2', '1')));
        $this->assertFalse($this->eventFilter->match($this->getEvent('/2/2', '2')));
        $this->assertFalse($this->eventFilter->match($this->getEvent('/3/2', null)));
    }

    /**
     * Get an Event with the given path.
     */
    protected function getEvent(string $path, ?string $id): Event
    {
        $event = new Event($this->factory, $this->getNodeTypeManager());
        $event->setPath($path);
        $event->setIdentifier($id);

        return $event;
    }

    /**
     * Set eventFilter to the identifiers.
     *
     * Additionally set the session getNodesByIdentifier to return an array of nodes
     * that will match the requirements of the filter
     *
     * @param string[] $identifiers
     */
    protected function setFilters(array $identifiers): void
    {
        $nodes = [];

        foreach ($identifiers as $uuid) {
            $nodes[] = $this->getMyNodeMock($uuid);
        }

        $this->session
            ->method('getNodesByIdentifier')
            ->willReturn(
                $nodes
            );

        $this->eventFilter->setIdentifiers($identifiers);
    }

    /**
     * Get a Jackalope\Node mock object that will return "/uuid" as path.
     */
    protected function getMyNodeMock(string $uuid): object
    {
        $node = $this->getNodeMock();
        $node
            ->method('getPath')
            ->willReturn('/'.$uuid)
        ;

        return $node;
    }
}
