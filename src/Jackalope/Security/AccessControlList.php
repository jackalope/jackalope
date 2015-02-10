<?php

namespace Jackalope\Security;

use Jackalope\Node;
use PHPCR\Security\AccessControlEntryInterface;
use PHPCR\Security\AccessControlException;
use PHPCR\Security\AccessControlListInterface;
use PHPCR\Security\PrincipalInterface;

/**
 * {@inheritDoc}
 */
class AccessControlList extends Node implements AccessControlListInterface
{
    private $bound = false;

    public function isModified()
    {
        return false;
    }

    public function isBound()
    {
        return $this->bound;
    }

    public function setBound($bound)
    {
        $this->bound =$bound;
    }

    /**
     * {@inheritDoc}
     */
    public function getAccessControlEntries()
    {
        // TODO: make these AccessControlEntry not node
        return $this->getNodes(null, 'rep:ACE');
    }

    /**
     * {@inheritDoc}
     */
    public function addAccessControlEntry(PrincipalInterface $principal, array $privileges)
    {
//        $this->add
        // TODO factory?
        $this->entries[] = new AccessControlEntry($principal, $privileges);
    }

    /**
     * {@inheritDoc}
     */
    public function removeAccessControlEntry(AccessControlEntryInterface $ace)
    {
        if ($key = array_search($ace, $this->entries, true)) {
            unset($this->entries[$key]);

            return;
        }

        throw new AccessControlException('Entry not found');
    }
}
