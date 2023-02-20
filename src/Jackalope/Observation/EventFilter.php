<?php

namespace Jackalope\Observation;

use Jackalope\FactoryInterface;
use Jackalope\NotImplementedException;
use PHPCR\Observation\EventFilterInterface;
use PHPCR\Observation\EventInterface;
use PHPCR\PathNotFoundException;
use PHPCR\PropertyInterface;
use PHPCR\SessionInterface;

/**
 * In addition to being a container, this filter implements the match method
 * to decide based on the set filters whether an event matches the filter.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 * @author Daniel Barsotti
 * @author David Buchmann
 */
class EventFilter implements EventFilterInterface
{
    private ?int $eventTypes = null;

    private ?string $absPath = null;

    private bool $isDeep = false;

    private ?array $identifiers = null;

    private ?array $nodeTypes = null;

    private bool $noLocal = false;

    private SessionInterface $session;

    public function __construct(FactoryInterface $factory, SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * {@inheritDoc}
     */
    public function match(EventInterface $event)
    {
        if ((null !== $this->eventTypes) && $this->skipByType($event)) {
            return false;
        }

        if ((null !== $this->absPath) && $this->skipByPath($event)) {
            return false;
        }

        if ((null !== $this->identifiers) && $this->skipByIdentifiers($event)) {
            return false;
        }

        if ((null !== $this->nodeTypes) && $this->skipByNodeTypes($event)) {
            return false;
        }

        if ($this->noLocal) {
            throw new NotImplementedException();
            /*
            if ($this->skipByNoLocal($event)) {
                return false;
            }
            */
        }

        return true;
    }

    /**
     * Bitwise and on the event type.
     */
    private function skipByType(EventInterface $event): bool
    {
        return !($event->getType() & $this->eventTypes);
    }

    private function skipByPath(EventInterface $event): bool
    {
        $eventPath = $event->getPath();
        if (!$this->isDeep && $eventPath !== $this->absPath) {
            // isDeep is false and the path is not the searched path
            return true;
        }
        if (null === $this->absPath) {
            return false;
        }

        // the node path does not start with the given path
        return null === $eventPath
            || strlen($eventPath) < strlen($this->absPath)
            || 0 !== strpos($eventPath, $this->absPath)
        ;
    }

    private function skipByIdentifiers(EventInterface $event): bool
    {
        if (!$identifier = $event->getIdentifier()) {
            // Some events (like PERSIST) do not provide an identifier
            return true;
        }

        return !in_array($identifier, $this->identifiers, true);
    }

    private function skipByNodeTypes(EventInterface $event): bool
    {
        if (!$path = $event->getPath()) {
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
    public function setEventTypes($eventTypes): self
    {
        $this->eventTypes = $eventTypes;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getEventTypes(): ?int
    {
        return $this->eventTypes;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setAbsPath($absPath): self
    {
        $this->absPath = $absPath;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getAbsPath(): ?string
    {
        return $this->absPath;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setIsDeep($isDeep): self
    {
        $this->isDeep = $isDeep;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getIsDeep(): bool
    {
        return $this->isDeep;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setIdentifiers(array $identifiers): self
    {
        $this->identifiers = $identifiers;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getIdentifiers(): ?array
    {
        return $this->identifiers;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function setNodeTypes(array $nodeTypes): self
    {
        $this->nodeTypes = $nodeTypes;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getNodeTypes(): ?array
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
        $this->noLocal = $noLocal;

        throw new NotImplementedException('TODO: how can we figure out if an event was local?');
        // return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getNoLocal(): bool
    {
        return $this->noLocal;
    }
}
