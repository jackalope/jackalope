<?php

namespace Jackalope\Security;

use Jackalope\FactoryInterface;
use PHPCR\NodeInterface;
use PHPCR\Security\AccessControlEntryInterface;
use PHPCR\Security\AccessControlException;
use PHPCR\Security\AccessControlListInterface;
use PHPCR\Security\AccessControlManagerInterface;
use PHPCR\Security\PrincipalInterface;

/**
 * {@inheritDoc}
 */
class AccessControlList extends AccessControlPolicy implements \IteratorAggregate, AccessControlListInterface
{
    private $aceList = array();

    /**
     * @param FactoryInterface $factory
     * @param NodeInterface $node
     */
    public function __construct(FactoryInterface $factory, AccessControlManagerInterface $acm, NodeInterface $node = null)
    {
        $this->factory = $factory;
        $this->acm = $acm;
        $this->node = $node;

        // TODO think about lazy initialization
        if ($this->node) {
            foreach ($this->node->getNodes(null, 'rep:ACE') as $aceNode) {
                $privileges = array();
                foreach ($aceNode->getPropertyValue('privileges') as $priv) {
                    $privileges[] = $this->acm->privilegeFromName($priv);
                }
                $this->aceList[] = new AccessControlEntry(new Principal($aceNode->getProperty('principal')), $privileges);
            }
        }

    }

    public function getIterator()
    {
        return new \ArrayIterator($this->getAccessControlEntries());
    }
    /**
     * {@inheritDoc}
     */
    public function getAccessControlEntries()
    {
        return $this->aceList;
    }

    /**
     * {@inheritDoc}
     */
    public function addAccessControlEntry(PrincipalInterface $principal, array $privileges)
    {
//        $this->add
        // TODO factory?
        $this->aceList[] = new AccessControlEntry($principal, $privileges);
    }

    /**
     * {@inheritDoc}
     */
    public function removeAccessControlEntry(AccessControlEntryInterface $ace)
    {
        if ($key = array_search($ace, $this->aceList, true)) {
            unset($this->aceList[$key]);

            return;
        }

        throw new AccessControlException('Entry not found');
    }
}
