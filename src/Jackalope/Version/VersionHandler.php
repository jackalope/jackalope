<?php

namespace Jackalope\Version;

use Jackalope\Session;
use Jackalope\Transport\AddNodeOperation;
use Jackalope\Transport\WritingInterface;
use PHPCR\InvalidItemStateException;
use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use PHPCR\UnsupportedRepositoryOperationException;
use PHPCR\Util\UUIDHelper;
use PHPCR\Version\VersionException;

class VersionHandler
{
    const MIX_VERSIONABLE = 'mix:versionable';
    const MIX_SIMPLE_VERSIONABLE = 'mix:simpleVersionable';

    /**
     * @var WritingInterface
     */
    private $objectManager;

    /**
     * @var Session
     */
    private $session;

    public function __construct(Session $session)
    {
        $this->objectManager = $session->getObjectManager();
        $this->session = $session;
    }

    public function addVersionProperties(NodeInterface $node)
    {
        $additionalOperations = array();

        if ($node->hasProperty('jcr:isCheckedOut')) {
            return $additionalOperations;
        }

        $session = $node->getSession();

        $node->setProperty('jcr:isCheckedOut', true);
        if (!$node->hasProperty('jcr:uuid')) {
            $node->setProperty('jcr:uuid', UUIDHelper::generateUUID());
        }

        $versionStorageNode = $session->getNode('/jcr:system/jcr:versionStorage');
        $versionHistory = $versionStorageNode->addNode($node->getIdentifier(), 'nt:versionHistory');
        $versionHistory->setProperty('jcr:uuid', UUIDHelper::generateUUID());
        $additionalOperations[] = new AddNodeOperation($versionHistory->getPath(), $versionHistory);
        $versionHistory->setProperty('jcr:versionableUuid', $node->getIdentifier());

        // TODO Set jcr:copiedFrom if needed

        $versionLabels = $versionHistory->addNode('jcr:versionLabels', 'nt:versionLabels');
        $additionalOperations[] = new AddNodeOperation($versionLabels->getPath(), $versionLabels);
        $rootVersion = $versionHistory->addNode('jcr:rootVersion', 'nt:version');
        $rootVersion->setProperty('jcr:uuid', UUIDHelper::generateUUID());
        $additionalOperations[] = new AddNodeOperation($rootVersion->getPath(), $rootVersion);

        // TODO add frozen node to root version

        $node->setProperty('jcr:versionHistory', $versionHistory);
        $node->setProperty('jcr:baseVersion', $rootVersion); // TODO set correct base version
        $node->setProperty('jcr:predecessors', array($rootVersion));

        return $additionalOperations;
    }

    /**
     * Performs a checkin for the node on the given path
     * @param $path
     * @return string
     */
    public function checkinItem($path)
    {
        $node = $this->objectManager->getNodeByPath($path);

        if (!$node->isNodeType(static::MIX_VERSIONABLE) || !$node->isNodeType(static::MIX_SIMPLE_VERSIONABLE)) {
            throw new UnsupportedRepositoryOperationException(
                'Node has to implement at least "mix:versionable" to use verisoning operations'
            );
        }

        if ($node->isModified()) {
            throw new InvalidItemStateException(sprintf(
                'Node "%s" contains unsaved changes',
                $path
            ));
        }

        if (!$node->isCheckedOut()) {
            return $path;
        }

        if ($node->hasProperty('jcr:mergeFailed')) {
            throw new VersionException(sprintf('Node "%s" contains unresolved merge conflicts', $path));
        }

        // TODO set subgraph to read only

        $versionStorageNode = $this->objectManager->getNodeByPath('/jcr:system/jcr:versionStorage');

        // FIXME add some kind of sharding
        $versionNode = $versionStorageNode->addNode(UUIDHelper::generateUUID(), 'nt:version');
        $versionNode->setProperty('jcr:created', new \DateTime());

        // TODO create frozen node

        // TODO also set the other predecessors and successors
        $versionNode->setProperty('jcr:predecessors', $node->getProperty('jcr:predecessors')->getString());

        // TODO change base version

        $node->setProperty('jcr:isCheckedOut', false, PropertyType::BOOLEAN, false);

        $this->session->save();

        return $versionNode->getPath();
    }
}
