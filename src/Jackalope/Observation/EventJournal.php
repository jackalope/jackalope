<?php

namespace Jackalope\Observation;

use PHPCR\Observation\EventJournalInterface,
    PHPCR\Observation\EventInterface,
    PHPCR\RepositoryException,
    PHPCR\SessionInterface;

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
     * @var \PHPCR\SessionInterface
     */
    protected $session;

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
     * The UUID criterion will filter out events on the journal which target node's parent
     * has not one of the specified UUIDs. In order to optimize, we will cache all the nodes
     * with the specified UUIDs.
     * @var array
     */
    protected $cachedNodesByUuid;

    /**
     * Construct a new EventJournal by extracting the $data that comes from the server.
     *
     * The journal does also receive the filter criteria so that if the server didn't filter the events,
     * there is still a chance to do so here. This class will consider that the journal was already
     * filtered by the server if it gets null in all the criterion parameter, that is if $eventTypes,
     * $absPath, $isDeep, $uuid and $nodeTypeName are all equal to null.
     *
     * We need the session in the event journal because if the backend didn't do any filtering on the
     * events, it's up to the EventJournal to do it. And some filter criteria require to access the parent
     * nodes which can only be done with the session.
     *
     * @param \Jackalope\FactoryInterface $factory
     * @param \PHPCR\SessionInterface $session
     * @param \DOMDocument $data The DOM data received from the DAVEX call to the server (might be already filtered or not)
     * @param int $eventTypes
     * @param string $absPath
     * @param boolean $isDeep
     * @param array $uuid
     * @param array $nodeTypeName
     * @param string $workspaceRootUri The prefix to extract the path from the event href attribute
     * @return \Jackalope\Observation\EventJournal
     *
     */
    public function __construct(FactoryInterface $factory, SessionInterface $session, \DOMDocument $data, $eventTypes = null, $absPath = null, $isDeep = null, array $uuid = null, array $nodeTypeName = null, $workspaceRootUri = '')
    {
        $this->factory = $factory;
        $this->session = $session;
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

        // Cache nodes for further filtering of the journal
        if ($this->uuidCriterion) {
            $this->cachedNodesByUuid = $this->session->getNodesByIdentifier($this->uuidCriterion);
        }

        // Construct the journal with the transport response
        $events = $this->constructEventJournal($data);

        // Filter the events if required
        if (!$this->alreadyFiltered) {
            $events = $this->filterEvents($events);
        }

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

    // ----- PROTECTED METHODS ------------------------------------------------

    /**
     * Get an array of event, filter them according to the filters properties of the class
     * and return the filtered array.
     * @param array(EventInterface) $events The unfiltered array of events
     * @return array(EventInterface) The filered array of events
     */
    protected function filterEvents($events)
    {
        $filteredEvents = array();

        foreach ($events as $event) {
            if ($this->matchCriteria($event)) {
                $filteredEvents[] = $event;
            }
        }

        return $filteredEvents;
    }

    /**
     * Return true if the given event match *all* (i.e. the criteria are ANDed) the filters
     * properties of the class and false otherwise.
     *
     * @param \PHPCR\Observation\EventInterface $event
     * @return boolean
     */
    protected function matchCriteria(EventInterface $event)
    {
        // Check the event type criterion
        if ($this->eventTypesCriterion && $event->getType() !== $this->eventTypesCriterion) {
            return false;
        }

        // Check the absPath and isDeep criteria
        if ($this->absPathCriterion) {

            if ($this->isDeepCriterion && !preg_match('/^' . $this->absPathCriterion . '/', $event->getPath())) {
                // isDeep is true and the node path does not start with the given path
                return false;
            } elseif ($event->getPath() !== $this->absPathCriterion) {
                // isDeep is false or not set and the path is not the searched path
                return false;
            }
        }

        // Check the uuid criterion
        if ($this->uuidCriterion) {

            // Foreach event in the journal
            // Construct the parent path
            // If one of the nodes in $this->cachedNodesByUuid has that path then:
            //    It means the parent of the current node has a parent with the given UUID ==>
            //    Keep the node
            // Otherwise:
            //    Filter it out

        }

        // Check the node type criterion
        if ($this->nodeTypeNameCriterion) {

            // TODO: implement naively (i.e. getting each node parent from the backend --> horrible performances)
            // then find a way to optimize
        }

        return true;
    }

    /**
     * Construct the event journal from the DAVEX response returned by the server
     * @param \DOMDocument $data
     * @return array(Event)
     */
    protected function constructEventJournal(\DOMDocument $data)
    {
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
            $event->setUserId($currentUserId);

            // The timestamps in Java contain milliseconds, it's not the case in PHP
            // so we strip millis from the response
            $event->setDate(substr($date->nodeValue, 0, -3));


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
