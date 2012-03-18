<?php

namespace Jackalope\Observation\Filter;

use PHPCR\Observation\EventInterface;

interface EventFilterInterface
{
    /**
     * @abstract
     * @param \PHPCR\Observation\EventInterface $event
     * @return boolean Whether the event should be kept or not
     */
    function match(EventInterface $event);
}
