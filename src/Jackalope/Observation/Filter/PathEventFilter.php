<?php

namespace Jackalope\Observation\Filter;

use PHPCR\Observation\EventInterface;


class PathEventFilter implements EventFilterInterface
{
    /**
     * @var string
     */
    protected $absPath;

    /**
     * @var boolean
     */
    protected $isDeep;

    /**
     * @param string $absPath
     * @param bool $isDeep
     */
    public function __construct($absPath, $isDeep = false)
    {
        $this->absPath = $absPath;
        $this->isDeep = $isDeep;
    }

    /**
     * {@inheritDoc}
     */
    public function match(EventInterface $event)
    {
        if ($this->isDeep && substr($event->getPath(), 0, strlen($this->absPath)) !== $this->absPath) {

            // isDeep is true and the node path does not start with the given path
            return false;

        } elseif (!$this->isDeep) {

            if ($event->getPath() !== $this->absPath) {
                // isDeep is false and the path is not the searched path
                return false;
            }
        }

        return true;
    }
}
