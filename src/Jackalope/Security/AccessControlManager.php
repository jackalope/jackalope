<?php

namespace Jackalope\Security;
use Jackalope\FactoryInterface;
use Jackalope\ObjectManager;
use Jackalope\Transport\AccessControlInterface;
use PHPCR\RepositoryException;
use PHPCR\Security\AccessControlManagerInterface;
use PHPCR\Security\AccessControlPolicyInterface;

/**
 * {@inheritDoc}
 */
class AccessControlManager implements AccessControlManagerInterface
{
    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var ObjectManager
     */
    private $om;

    /**
     * @var AccessControlInterface
     */
    private $transport;

    public function __construct(FactoryInterface $factory, ObjectManager $om, AccessControlInterface $transport)
    {
        $this->factory = $factory;
        $this->om = $om;
        $this->transport = $transport;
    }

    /**
     * {@inheritDoc}
     */
    public function getSupportedPrivileges($absPath = null)
    {
        return $this->transport->getSupportedPrivileges($absPath);
    }

    /**
     * {@inheritDoc}
     */
    public function privilegeFromName($privilegeName)
    {
        return new Privilege($privilegeName);
    }

    /**
     * {@inheritDoc}
     */
    public function hasPrivileges($absPath, array $privileges)
    {
        // TODO
    }

    /**
     * {@inheritDoc}
     */
    public function getPrivileges($absPath = null)
    {
        // TODO
    }

    /**
     * {@inheritDoc}
     */
    public function getPolicies($absPath)
    {
        return $this->om->getPolicies($absPath);
    }

    /**
     * {@inheritDoc}
     */
    public function getEffectivePolicies($absPath)
    {
        throw new RepositoryException('This method can not properly be implemented. Use getPolicies for this path');
    }

    /**
     * {@inheritDoc}
     */
    public function getApplicablePolicies($absPath)
    {
//        return array(new AccessControlList());
        // only bind on setPolicy
    }

    /**
     * {@inheritDoc}
     */
    public function setPolicy($absPath, AccessControlPolicyInterface $policy)
    {
        // TODO: track this so its saved
    }

    /**
     * {@inheritDoc}
     */
    public function removePolicy($absPath, AccessControlPolicyInterface $policy)
    {
        // TODO: track this so its saved
    }
}
