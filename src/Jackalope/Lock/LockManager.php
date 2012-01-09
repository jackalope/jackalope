<?php

namespace Jackalope\Lock;

use PHPCR\Lock\LockManagerInterface,
    Jackalope\ObjectManager,
    Jackalope\FactoryInterface,
    Jackalope\NotImplementedException;

/**
 * {@inheritDoc}
 *
 * @api
 *
 * @author D. Barsotti <daniel.barsotti@liip.ch>
 */
class LockManager implements \IteratorAggregate, LockManagerInterface
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
     * @param \Jackalope\FactoryInterface $factory An object factory implementing "get" as described in \Jackalope\FactoryInterface
     * @param \Jackalope\ObjectManager $objectManager
     * @return \Jackalope\Lock\LockManager
     */
    public function __construct(FactoryInterface $factory, ObjectManager $objectManager)
    {
        $this->objectmanager = $objectManager;
        $this->factory = $factory;
    }

    public function getIterator() {
        // TODO: return an iterator over getLockTokens() results
        return new ArrayIterator($this);
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
        return $this->objectmanager->holdsLock($absPath);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function lock($absPath, $isDeep, $isSessionScoped, $timeoutHint, $ownerInfo)
    {
        return $this->objectmanager->lockNode($absPath, $isDeep, $isSessionScoped, $timeoutHint, $ownerInfo);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    function isLocked($absPath)
    {
        return $this->objectmanager->isLocked($absPath);
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
        $this->objectmanager->unlock($absPath);
    }
}