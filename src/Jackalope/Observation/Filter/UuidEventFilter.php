<?php

namespace Jackalope\Observation\Filter;

use PHPCR\SessionInterface,
    PHPCR\Observation\EventInterface;


class UuidEventFilter implements EventFilterInterface
{
    /**
     * @var \PHPCR\SessionInterface
     */
    protected $session;

    /**
     * @var array
     */
    protected $uuids;

    /**
     * The UUID criterion will filter out events on the journal which target node's parent
     * has not one of the specified UUIDs. In order to optimize, we will cache all the nodes
     * with the specified UUIDs.
     * @var array
     */
    protected $cachedNodesByUuid;

    /**
     * @param array $uuids
     */
    public function __construct(SessionInterface $session, $uuids)
    {
        $this->session = $session;
        $this->uuids = $uuids;
        $this->cachedNodesByUuid = $this->session->getNodesByIdentifier($this->uuids);
    }

    /**
     * {@inheritDoc}
     */
    public function match(EventInterface $event)
    {
        if (!$event->getPath()) {
            // Some events (like PERSIST) do not contain a path
            return false;
        }

        // Algorithm:
        //  Construct the parent path
        //  If one of the nodes in $this->cachedNodesByUuid has that path then:
        //    It means the parent of the current node has one of the given UUID and the node must be kept
        //  Otherwise:
        //    Filter it out

        return $this->nodeIsCached($this->getParentPath($event->getPath()));
    }

    protected function nodeIsCached($path)
    {
        foreach ($this->cachedNodesByUuid as $node) {
            if ($node->getPath() === $path) {
                return true;
            }
        }
        return false;
    }

    /**
     * Construct the path of the parent from the given path
     * TODO: this should be extracted into an helper
     * @param string $path
     * @return string
     */
    protected function getParentPath($path)
    {
        return strtr(dirname($path), '\\', '/');
    }
}
