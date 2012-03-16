<?php

namespace Jackalope\Observation;

use PHPCR\Observation\EventJournalInterface,
    PHPCR\Observation\EventInterface,
    PHPCR\RepositoryException;

use Jackalope\FactoryInterface;


/**
 * {@inheritDoc}
 *
 * @api
 *
 * @author D. Barsotti <daniel.barsotti@liip.ch>
 */
class EventJournal extends \ArrayIterator implements EventJournalInterface
{
    /**
     * @var \Jackalope\FactoryInterface
     */
    protected $factory;

    /**
     * @var string
     */
    protected $workspaceRootUri;

    /**
     * Has the journal already been filtered by the server?
     * @var bool
     */
    protected $alreadyFiltered;

    /**
     * @var int
     */
    protected $eventTypesCriterion;

    /**
     * @var string
     */
    protected $absPathCriterion;

    /**
     * @var boolean
     */
    protected $isDeepCriterion;

    /**
     * @var array
     */
    protected $uuidCriterion;

    /**
     * @var array
     */
    protected $nodeTypeNameCriterion;


    /**
     * Construct a new EventJournal by extracting the $data that comes from the server.
     *
     * The journal does also receive the filter criteria so that if the server didn't filter the events,
     * there is still a chance to do so here. This class will consider that the journal was already
     * filtered by the server if it gets null in all the criterion parameter, that is if $eventTypes,
     * $absPath, $isDeep, $uuid and $nodeTypeName are all equal to null.
     *
     * @param \Jackalope\FactoryInterface $factory
     * @param \DOMDocument $data The DOM data received from the DAVEX call to the server (might be already filtered or not)
     * @param int $eventTypes
     * @param string $absPath
     * @param boolean $isDeep
     * @param array $uuid
     * @param array $nodeTypeName
     * @param string $workspaceRootUri The prefix to extract the path from the event href attribute
     */
    public function __construct(FactoryInterface $factory, \DOMDocument $data, $eventTypes = null, $absPath = null, $isDeep = null, array $uuid = null, array $nodeTypeName = null, $workspaceRootUri = '')
    {
        $this->factory = $factory;
        $this->workspaceRootUri = $workspaceRootUri;

        $this->eventTypesCriterion = $eventTypes;
        $this->absPathCriterion = $absPath;
        $this->isDeepCriterion = $isDeep;
        $this->uuidCriterion = $uuid;
        $this->nodeTypeNameCriterion = $nodeTypeName;

        $this->alreadyFiltered =
                 ($eventTypes !== null) || ($absPath !== null)
              || ($isDeep !== null) || ($uuid !== null)
              || ($nodeTypeName !== null);

        $events = $this->constructEventJournal($data);

        parent::__construct($events);
    }

    /**
     * {@inheritDoc}
     * @api
     */
    public function skipTo($date)
    {
        $event = $this->current();
        while ($event && $event->getDate() < $date) {
            $this->next();
            $event = $this->current();
        }
    }

    /**
     * Construct the event journal from the DAVEX response returned by the server
     * @param \DOMDocument $data
     * @return array(Event)
     */
    protected function constructEventJournal(\DOMDocument $data)
    {
        //var_dump($data->saveXML());die;

        $events = array();
        $entries = $data->getElementsByTagName('entry');

        foreach ($entries as $entry) {

            $userId = $this->extractUserId($entry);
            $moreEvents = $this->extractEvents($entry, $userId);
            $events = array_merge($events, $moreEvents);
        }

        return $events;
    }

    /**
     * Parse the events in an <entry> section
     * @param \DOMElement $entry
     * @param string $currentUserId The current user ID as extracted from the <entry> part
     * @return array(Event)
     */
    protected function extractEvents(\DOMElement $entry, $currentUserId)
    {
        $events = array();
        $domEvents = $entry->getElementsByTagName('event');

        foreach ($domEvents as $domEvent) {

            $event = new Event();
            $event->setType($this->extractEventType($domEvent));

            $date = $this->getDomElement($domEvent, 'eventdate', 'The event date was not found while building the event journal:\n' . $this->getEventDom($domEvent));
            $event->setDate($date->nodeValue);
            $event->setUserId($currentUserId);

            $id = $this->getDomElement($domEvent, 'eventidentifier');
            if ($id) {
                $event->setIdentifier($id->nodeValue);
            }

            $href = $this->getDomElement($domEvent, 'href');
            if ($href) {
                $event->setPath(str_replace($this->workspaceRootUri, '', $href->nodeValue));
            }

            $nodeType = $this->getDomElement($domEvent, 'eventprimarynodetype');
            if ($nodeType) {
                $event->setNodeType($nodeType->nodeValue);
            }

            $userData = $this->getDomElement($domEvent, 'eventuserdata');
            if ($userData) {
                $event->setUserData($userData->nodeValue);
            }

            // TODO: extract the info

            $events[] = $event;

            // TODO: Remove debug code
//            var_dump($data->saveXML($domEvent));
//            var_dump(str_repeat('-', 80));
//            var_dump($event);
//            var_dump(str_repeat('-', 80));
        }

        return $events;
    }

    /**
     * Extract a user id from the author tag in an entry section
     * @throws \PHPCR\RepositoryException
     * @param \DOMElement $entry
     * @return void
     */
    protected function extractUserId(\DOMElement $entry)
    {
        $authors = $entry->getElementsByTagName('author');

        if (!$authors->length) {
            throw new RepositoryException("User ID not found while building the event journal");
        }

        $userId = null;
        foreach ($authors->item(0)->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                return $child->nodeValue;
            }
        }

        throw new RepositoryException("Malformed user ID while building the event journal");
    }

    /**
     * Extract an event type from a DAVEX event journal response
     * @throws RepositoryException
     * @param \DOMElement $event
     * @return int The event type
     */
    protected function extractEventType(\DOMElement $event)
    {
        $list = $event->getElementsByTagName('eventtype');

        if (!$list->length) {
            throw new RepositoryException("Event type not found while building the event journal");
        }

        // Here we cannot simply take the first child as the <eventtype> tag might contain
        // text fragments (i.e. newlines) that will be returned as DOMText elements.
        $type = null;
        foreach($list->item(0)->childNodes as $el) {
            if ($el instanceof \DOMElement) {
                return $this->getEventTypeFromTagName($el->tagName);
            }
        }

        throw new RepositoryException("Malformed event type while building the event journal");
    }

    /**
     * Extract a given DOMElement from the children of another DOMElement
     *
     * @throws RepositoryException
     * @param \DOMElement $event The DOMElement containing the searched tag
     * @param string $tagName The name of the searched tag
     * @param string $errorMessage The error message when the tag was not found or null if the tag is not required
     * @return \DOMNode
     */
    protected function getDomElement(\DOMElement $event, $tagName, $errorMessage = null)
    {
        $list = $event->getElementsByTagName($tagName);

        if ($errorMessage && !$list->length) {
            throw new RepositoryException($errorMessage);
        }

        return $list->item(0);
    }

    /**
     * Get the JCR event type from a DAVEX tag representing the event type
     * @throws RepositoryException
     * @param string $tagName
     * @return int
     */
    protected function getEventTypeFromTagName($tagName)
    {
        switch (strtolower($tagName)) {
            case 'nodeadded': return EventInterface::NODE_ADDED;
            case 'noderemoved': return EventInterface::NODE_REMOVED;
            case 'propertyadded': return EventInterface::PROPERTY_ADDED;
            case 'propertyremoved': return EventInterface::PROPERTY_REMOVED;
            case 'propertychanged': return EventInterface::PROPERTY_CHANGED;
            case 'nodemoved': return EventInterface::NODE_MOVED;
            case 'persist': return EventInterface::PERSIST;
            default: throw new RepositoryException(sprintf("Invalid event type '%s'", $tagName));
        }
    }

    /**
     * Get the XML representation of a DOMElement to display in error messages
     * @param \DOMElement $event
     * @return string
     */
    protected function getEventDom(\DOMElement $event)
    {
        return $event->ownerDocument->saveXML($event);
    }
}
