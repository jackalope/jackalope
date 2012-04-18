<?php

namespace Jackalope\Observation\Filter;

use PHPCR\Observation\EventInterface;

/**
 * Event filter that execute a list of other event filters. It stops the chain
 * as soon as a filter rejects an event. Thus it can be used to AND event
 * filters.
 */
class EventFilterChain implements EventFilterInterface
{
    protected $filters;


    public function __construct()
    {
        $this->filters = array();
    }

    /**
     * Factory method to construct a EventFilterChain from the parameters given to ObservationManager::getJournal
     *
     * @static
     * @param \PHPCR\SessionInterface $session
     * @param int|null $eventTypes
     * @param string|null $absPath
     * @param boolean|null $isDeep
     * @param array|null $uuid
     * @param array|null $nodeTypeName
     * @return EventFilterChain
     */
    public static function constructFilterChain(\PHPCR\SessionInterface $session, $eventTypes = null, $absPath = null, $isDeep = null, array $uuid = null, array $nodeTypeName = null)
    {
        $filter = new EventFilterChain();

        if (!is_null($eventTypes)) {
            $filter->addFilter(new EventTypeEventFilter($eventTypes));
        }

        if (!is_null($absPath)) {
            $filter->addFilter(new PathEventFilter($absPath, $isDeep));
        }

        if (!is_null($uuid)) {
            $filter->addFilter(new UuidEventFilter($session, $uuid));
        }

        if (!is_null($nodeTypeName)) {
            $filter->addFilter(new NodeTypeEventFilter($session, $nodeTypeName));
        }

        return $filter;
    }

    /**
     * Add a filter to the filter chain
     * @param EventFilterInterface $filter
     * @return void
     */
    public function addFilter(EventFilterInterface $filter)
    {
        $this->filters[] = $filter;
    }

    /**
     * {@inheritDoc}
     */
    public function match(EventInterface $event)
    {
        foreach ($this->filters as $filter)
        {
            if (!$filter->match($event)) {
                return false;
            }
        }

        return true;
    }
}
