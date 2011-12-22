<?php

namespace Jackalope\Lock;

use PHPCR\Lock\LockManagerInterface,
    Jackalope\ObjectManager;

/**
 * {@inheritDoc}
 *
 * @api
 */
class LockManager implements LockManagerInterface
{
    /**
     * @var \Jackalope\ObjectManager
     */
    protected $objectmanager;

    /**
     * The jackalope object factory for this object
     * @var \Jackalope\Factory
     */
    protected $factory;

    /**
     * Create the version manager - there should be only one per session.
     *
     * @param \Jackalope\Factory $factory An object factory implementing "get" as described in \Jackalope\Factory
     * @param \Jackalope\ObjectManager $objectManager
     * @return \Jackalope\Lock\LockManager
     */
    public function __construct($factory, ObjectManager $objectManager)
    {
        $this->objectmanager = $objectManager;
        $this->factory = $factory;
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function addLockToken($lockToken)
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function getLock($absPath)
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function getLockTokens()
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function holdsLock($absPath)
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function lock($absPath, $isDeep, $isSessionScoped, $timeoutHint, $ownerInfo)
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function isLocked($absPath)
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function removeLockToken($lockToken)
    {
        throw new NotImplementedException();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function unlock($absPath)
    {
        throw new NotImplementedException();
    }
}