<?php

namespace Jackalope\Observation;

use DOMDocument;
use DOMElement;
use DOMNode;
use ArrayIterator;

use PHPCR\Observation\EventJournalInterface;
use PHPCR\Observation\EventInterface;
use PHPCR\RepositoryException;
use PHPCR\SessionInterface;

use Jackalope\FactoryInterface;

/**
 * {@inheritDoc}
 *
 * @api
 *
 * @author D. Barsotti <daniel.barsotti@liip.ch>
 */
class EventJournal extends ArrayIterator implements EventJournalInterface
{
    /**
     * @var FactoryInterface
     */
    protected $factory;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var EventFilter
     */
    protected $filter;

    /**
     * @var string
     */
    protected $workspaceRootUri;


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
     * @param FactoryInterface $factory
     * @param SessionInterface $session
     * @param DOMDocument     $data             The DOM data received from the DAVEX call to the server (might be already filtered or not)
     * @param EventFilter      $filter           the event filter to apply
     * @param string           $workspaceRootUri The prefix to extract the path from the event href attribute
     *
     * @return EventJournal
     *
     */
    public function __construct(FactoryInterface $factory, SessionInterface $session, DOMDocument $data, EventFilter $filter, $workspaceRootUri = '')
    {
        $this->factory = $factory;
        $this->session = $session;
        $this->workspaceRootUri = $workspaceRootUri;

        $this->filter = $filter;

        // Construct the journal with the transport response
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

    // ----- PROTECTED METHODS ------------------------------------------------

    /**
     * Construct the event journal from the DAVEX response returned by the
     * server, immediately filtered by the current filter.
     *
     * @param DOMDocument $data
     *
     * @return Event[]
     */
    protected function constructEventJournal(DOMDocument $data)
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
     * @param DOMElement $entry
     * @param string $currentUserId The current user ID as extracted from the <entry> part
     * @return Event[]
     */
    protected function extractEvents(DOMElement $entry, $currentUserId)
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
                $path = str_replace($this->workspaceRootUri, '', $href->nodeValue);
                if (substr($path, -1) === '/') {
                    // Jackrabbit might return paths with trailing slashes. Eliminate them if present.
                    $path = substr($path, 0, -1);
                }
                $event->setPath($path);

            }

            $nodeType = $this->getDomElement($domEvent, 'eventprimarynodetype');
            if ($nodeType) {
                $event->setNodeType($nodeType->nodeValue);
            }

            $userData = $this->getDomElement($domEvent, 'eventuserdata');
            if ($userData) {
                $event->setUserData($userData->nodeValue);
            }

            $eventInfos = $this->getDomElement($domEvent, 'eventinfo');
            if ($eventInfos) {
                foreach ($eventInfos->childNodes as $info) {
                    if ($info->nodeType == XML_ELEMENT_NODE) {
                        $event->addInfo($info->tagName, $info->nodeValue);
                    }
                }
            }

            if ($this->filter->match($event)) {
                $events[] = $event;
            }
        }

        return $events;
    }

    /**
     * Extract a user id from the author tag in an entry section
     *
     * @param DOMElement $entry
     *
     * @return string user id of the event
     *
     * @throws RepositoryException
     */
    protected function extractUserId(DOMElement $entry)
    {
        $authors = $entry->getElementsByTagName('author');

        if (!$authors->length) {
            throw new RepositoryException("User ID not found while building the event journal");
        }

        $userId = null;
        foreach ($authors->item(0)->childNodes as $child) {
            if ($child instanceof DOMElement) {
                return $child->nodeValue;
            }
        }

        throw new RepositoryException("Malformed user ID while building the event journal");
    }

    /**
     * Extract an event type from a DAVEX event journal response
     * @throws RepositoryException
     * @param DOMElement $event
     * @return int The event type
     */
    protected function extractEventType(DOMElement $event)
    {
        $list = $event->getElementsByTagName('eventtype');

        if (!$list->length) {
            throw new RepositoryException("Event type not found while building the event journal");
        }

        // Here we cannot simply take the first child as the <eventtype> tag might contain
        // text fragments (i.e. newlines) that will be returned as DOMText elements.
        $type = null;
        foreach($list->item(0)->childNodes as $el) {
            if ($el instanceof DOMElement) {
                return $this->getEventTypeFromTagName($el->tagName);
            }
        }

        throw new RepositoryException("Malformed event type while building the event journal");
    }

    /**
     * Extract a given DOMElement from the children of another DOMElement
     *
     * @param DOMElement $event        The DOMElement containing the searched tag
     * @param string     $tagName      The name of the searched tag
     * @param string     $errorMessage The error message when the tag was not found or null if the tag is not required
     *
     * @return DOMNode
     *
     * @throws RepositoryException
     */
    protected function getDomElement(DOMElement $event, $tagName, $errorMessage = null)
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
            case 'nodeadded':
                return EventInterface::NODE_ADDED;
            case 'noderemoved':
                return EventInterface::NODE_REMOVED;
            case 'propertyadded':
                return EventInterface::PROPERTY_ADDED;
            case 'propertyremoved':
                return EventInterface::PROPERTY_REMOVED;
            case 'propertychanged':
                return EventInterface::PROPERTY_CHANGED;
            case 'nodemoved':
                return EventInterface::NODE_MOVED;
            case 'persist':
                return EventInterface::PERSIST;
            default:
                throw new RepositoryException(sprintf("Invalid event type '%s'", $tagName));
        }
    }

    /**
     * Get the XML representation of a DOMElement to display in error messages
     * @param DOMElement $event
     * @return string
     */
    protected function getEventDom(DOMElement $event)
    {
        return $event->ownerDocument->saveXML($event);
    }
}
