<?php

namespace Jackalope\Observation;

use PHPCR\Observation\EventJournalInterface;

/**
 * {@inheritDoc}
 *
 * @api
 *
 * @author D. Barsotti <daniel.barsotti@liip.ch>
 */
class EventJournal implements EventJournalInterface
{

    /**
     * {@inheritDoc}
     * @api
     */
    function skipTo($date)
    {
        throw new \Jackalope\NotImplementedException();
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        throw new \Jackalope\NotImplementedException();
    }

    /**
     * {@inheritDoc}
     */
    public function seek($position)
    {
        throw new \Jackalope\NotImplementedException();
    }

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        throw new \Jackalope\NotImplementedException();
    }

    /**
     * {@inheritDoc}
     */
    public function next()
    {
        throw new \Jackalope\NotImplementedException();
    }

    /**
     * {@inheritDoc}
     */
    public function key()
    {
        throw new \Jackalope\NotImplementedException();
    }

    /**
     * {@inheritDoc}
     */
    public function valid()
    {
        throw new \Jackalope\NotImplementedException();
    }

    /**
     * {@inheritDoc}
     */
    public function rewind()
    {
        throw new \Jackalope\NotImplementedException();
    }
}
