<?php

namespace Jackalope\Observation\Filter;

use PHPCR\SessionInterface,
    PHPCR\Observation\EventInterface;

/**
 * Naive implementation of the event filter based on the node type of the
 * node attached to the event. This implementation does not take care of
 * optimizations.
 *
 * TODO: subclass this and optimize
 */
class NodeTypeEventFilter implements EventFilterInterface
{
    /**
     * @var \PHPCR\SessionInterface
     */
    protected $session;

    /**
     * @var array
     */
    protected $nodeTypes;

    /**
     * @param array $nodeTypes
     */
    public function __construct(SessionInterface $session, $nodeTypes)
    {
        $this->session = $session;
        $this->nodeTypes = $nodeTypes;
    }

    /**
     * {@inheritDoc}
     */
    public function match(EventInterface $event)
    {
        if (!$event->getPath()) {
            // Some events (like PERSIST) don't have a path
            return false;
        }

        $node = $this->session->getNode($event->getPath());
        foreach ($this->nodeTypes as $type) {
            if ($node->isNodeType($type)) {
                return true;
            }
        }

        return false;
    }
}
