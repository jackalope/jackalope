<?php

namespace Jackalope\Security;
use Jackalope\FactoryInterface;
use Jackalope\ObjectManager;
use Jackalope\Transport\AccessControlInterface;
use PHPCR\RepositoryException;
use PHPCR\Security\AccessControlException;
use PHPCR\Security\AccessControlManagerInterface;
use PHPCR\Security\AccessControlPolicyInterface;
use PHPCR\Security\PrivilegeInterface;

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

    /**
     * @var PrivilegeInterface[]
     */
    private $supportedPrivilegesByPath = array();

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
        if (!isset($this->supportedPrivilegesByPath[$absPath])) {
            $this->supportedPrivilegesByPath[$absPath] = $this->transport->getSupportedPrivileges($absPath);
        }

        return $this->supportedPrivilegesByPath[$absPath];
    }

    /**
     * {@inheritDoc}
     */
    public function privilegeFromName($privilegeName)
    {
        foreach ($this->getSupportedPrivileges() as $privilege) {
            if ($privilegeName === $privilege->getName()) {
                return $privilege;
            }

            foreach ($privilege->getAggregatePrivileges() as $childPrivilege) {
                if ($privilegeName === $childPrivilege->getName()) {
                    return $childPrivilege;
                }
            }
        }

        throw new AccessControlException($privilegeName);
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
        if (count($this->getPolicies($absPath)) {
            return array();
        }

        return array(new AccessControlList($this->factory));

        // if there is no accees control list on this path yet, we return a new unbound list
        // otherwise its just an empty array.

//        return array(new AccessControlList());
        // only bind on setPolicy
    }

    /**
     * {@inheritDoc}
     */
    public function setPolicy($absPath, AccessControlPolicyInterface $policy)
    {
        if (!$policy instanceof AccessControlList) {
            throw new \InvalidArgumentException('Invalid policy given');
        }

        $this->om->setPolicy($absPath, $policy);
    }

    /**
     * {@inheritDoc}
     */
    public function removePolicy($absPath, AccessControlPolicyInterface $policy)
    {
        // TODO: track this so its saved
    }
}
