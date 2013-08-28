<?php

namespace Jackalope\Observation;

use PHPCR\SessionInterface;
use PHPCR\PropertyInterface;
use PHPCR\Observation\EventFilterInterface;
use PHPCR\Observation\EventInterface;
use PHPCR\PathNotFoundException;

use Jackalope\FactoryInterface;

/**
 * In addition to being a container, this filter implements the match method
 * to decide based on the set filters whether an event matches the filter.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @author Daniel Barsotti
 * @author David Buchmann
 */
class EventFilter implements EventFilterInterface
{
    private $eventTypes = null;

    private $absPath = null;

    private $isDeep = false;

    private $identifiers = null;

    private $nodeTypes = null;

    private $noLocal = false;

    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(FactoryInterface $factory, SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * {@inheritDoc}
     */
    public function match(EventInterface $event)
    {
        if (!is_null($this->eventTypes)) {
            if ($this->skipByType($event)) {
                return false;
            }
        }
        if (!is_null($this->absPath)) {
            if ($this->skipByPath($event)) {
                return false;
            }
        }
        if (!is_null($this->identifiers)) {
            if ($this->skipByIdentifiers($event)) {
                return false;
            }
        }
        if (!is_null($this->nodeTypes)) {
            if ($this->skipByNodeTypes($event)) {
                return false;
            }
        }
        if ($this->noLocal) {
            throw new \Jackalope\NotImplementedException;
            if ($this->skipByNoLocal($event)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Bitwise and on the event type
     */
    private function skipByType(EventInterface $event)
    {
        return ! ($event->getType() & $this->eventTypes);
    }

    private function skipByPath(EventInterface $event)
    {
        $eventPath = $event->getPath();
        if (! $this->isDeep && $eventPath !== $this->absPath) {
            // isDeep is false and the path is not the searched path
            return true;
        }

        if (strlen($eventPath) < strlen($this->absPath)
            || substr($eventPath, 0, strlen($this->absPath)) != $this->absPath
        ) {
            // the node path does not start with the given path
            return true;
        }

        return false;
    }

    private function skipByIdentifiers(EventInterface $event)
    {
        if (! $identifier = $event->getIdentifier()) {
            // Some events (like PERSIST) do not provide an identifier
            return true;
        }

        return ! in_array($identifier, $this->identifiers);
    }

    private function skipByNodeTypes(EventInterface $event)
    {
        if (! $path = $event->getPath()) {
            // Some events (like PERSIST) do not provide an identifier
            return true;
        }
        try {
            $node = $this->session->getItem($path);
        } catch (PathNotFoundException $e) {
            return true;
        }
        if ($node instanceof PropertyInterface) {
            $node = $node->getParent();
        }
        foreach ($this->nodeTypes as $typename) {
            if ($node->isNodeType($typename)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setEventTypes($eventTypes)
    {
        $this->eventTypes = $eventTypes;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getEventTypes()
    {
        return $this->eventTypes;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setAbsPath($absPath)
    {
        $this->absPath = $absPath;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAbsPath()
    {
        return $this->absPath;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setIsDeep($isDeep)
    {
        $this->isDeep = $isDeep;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getIsDeep()
    {
        return $this->isDeep;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setIdentifiers(array $identifiers)
    {
        $this->identifiers = $identifiers;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getIdentifiers()
    {
        return $this->identifiers;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setNodeTypes(array $nodeTypes)
    {
        $this->nodeTypes = $nodeTypes;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getNodeTypes()
    {
        return $this->nodeTypes;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setNoLocal($noLocal)
    {
        throw new \Jackalope\NotImplementedException('TODO: how can we figure out if an event was local?');
        $this->noLocal = $noLocal;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getNoLocal()
    {
        return $this->noLocal;
    }
}
