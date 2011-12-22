<?php

namespace Jackalope\Lock;

use PHPCR\Lock\LockInterface;

/**
 * {@inheritDoc}
 *
 * @api
 */
class Lock implements LockInterface
{

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function getLockOwner()
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function isDeep()
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function getNode()
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function getLockToken()
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function getSecondsRemaining()
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function isLive()
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function isSessionScoped()
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function isLockOwningSession()
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function refresh()
    {
        throw new NotImplementedException();
    }
}
