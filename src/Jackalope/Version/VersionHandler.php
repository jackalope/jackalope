<?php

namespace Jackalope\Version;

use Jackalope\Session;
use Jackalope\Transport\AddNodeOperation;
use Jackalope\Transport\WritingInterface;
use PHPCR\InvalidItemStateException;
use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use PHPCR\PropertyType;
use PHPCR\UnsupportedRepositoryOperationException;
use PHPCR\Util\UUIDHelper;
use PHPCR\Version\OnParentVersionAction;
use PHPCR\Version\VersionException;

/**
 * This class provides a basic implementation of the versioning capabilities as described in the JCR specification. It
 * follows the specification, and can be used for any transport layer. However, there might be some performance tweaks
 * which can be applied to certain transport layers.
 *
 * @see http://www.day.com/specs/jcr/2.0/15_Versioning.html
 */
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

    /**
     * Adds the required version properties and nodes to the given node. Returns an array for the creation of the
     * versioning nodes, which will be handled in the NodeProcessor.
     * @param NodeInterface $node
     * @return array
     */
    public function addVersionProperties(NodeInterface $node)
    {
        if ($node->hasProperty('jcr:isCheckedOut')) {
            // Versioning properties have already been initialized, nothing to do
            return array();
        }

        $additionalOperations = array();

        $session = $node->getSession();

        $node->setProperty('jcr:isCheckedOut', true);
        if (!$node->hasProperty('jcr:uuid')) {
            $node->setProperty('jcr:uuid', UUIDHelper::generateUUID());
        }

        $versionStorageNode = $session->getNode('/jcr:system/jcr:versionStorage');
        $versionHistory = $versionStorageNode->addNode($node->getIdentifier(), 'nt:versionHistory');
        $versionHistory->setProperty('jcr:uuid', UUIDHelper::generateUUID());
        $versionHistory->setProperty('jcr:versionableUuid', $node->getIdentifier());
        $additionalOperations[] = new AddNodeOperation($versionHistory->getPath(), $versionHistory);

        // TODO Set jcr:copiedFrom if needed

        $versionLabels = $versionHistory->addNode('jcr:versionLabels', 'nt:versionLabels');
        $additionalOperations[] = new AddNodeOperation($versionLabels->getPath(), $versionLabels);
        $rootVersion = $versionHistory->addNode('jcr:rootVersion', 'nt:version');
        $rootVersion->setProperty('jcr:uuid', UUIDHelper::generateUUID());
        $rootVersion->setProperty('jcr:predecessors', array(), PropertyType::REFERENCE);
        // not part of the specification, but seems to be required
        $rootVersion->setProperty('jcr:successors', array(), PropertyType::REFERENCE);
        $additionalOperations[] = new AddNodeOperation($rootVersion->getPath(), $rootVersion);

        // TODO add frozen node to root version

        $node->setProperty('jcr:versionHistory', $versionHistory);
        $node->setProperty('jcr:baseVersion', $rootVersion);
        $node->setProperty('jcr:predecessors', array($rootVersion));

        return $additionalOperations;
    }

    /**
     * Performs a checkin for the node on the given path
     *
     * @param string $path The absolute path of the node to checkin
     *
     * @return string The path to the node containing the version information
     */
    public function checkinItem($path)
    {
        $node = $this->objectManager->getNodeByPath($path);

        if (!$node->isNodeType(static::MIX_SIMPLE_VERSIONABLE)) {
            throw new UnsupportedRepositoryOperationException(
                'Node has to implement at least "mix:versionable" to use verisoning operations'
            );
        }

        if ($node->isModified()) {
            throw new InvalidItemStateException(
                sprintf(
                    'Node "%s" contains unsaved changes',
                    $path
                )
            );
        }

        $baseVersionUuid = $node->getProperty('jcr:baseVersion')->getString();
        $baseVersionNode = $this->objectManager->getNodeByIdentifier($baseVersionUuid, 'Version\Version');

        if (!$node->isCheckedOut()) {
            return $baseVersionNode->getPath();
        }

        if ($node->hasProperty('jcr:mergeFailed')) {
            throw new VersionException(sprintf('Node "%s" contains unresolved merge conflicts', $path));
        }

        // TODO set subgraph to read only

        /** @var NodeInterface $versionHistoryNode */
        $versionHistoryUuid = $node->getProperty('jcr:versionHistory')->getString();
        $versionHistoryNode = $this->objectManager->getNodeByIdentifier($versionHistoryUuid, 'Version\VersionHistory');

        // FIXME add some kind of sharding
        // should avoid to have too many nodes on the same level
        $versionNode = $versionHistoryNode->addNode(UUIDHelper::generateUUID(), 'nt:version');
        $versionNode->setProperty('jcr:uuid', UUIDHelper::generateUUID());
        $versionNode->setProperty('jcr:created', new \DateTime());
        $versionNode->setProperty('jcr:successors', array());

        $this->createFrozenNode($versionNode, $node);

        $baseVersionNode->setProperty('jcr:successors', array($versionNode), PropertyType::REFERENCE, false);
        $baseVersionNode->setModified();
        $versionNode->setProperty(
            'jcr:predecessors',
            $node->getProperty('jcr:predecessors')->getString(),
            PropertyType::REFERENCE
        );
        $versionNode->setProperty(
            'jcr:successors',
            array(),
            PropertyType::REFERENCE,
            false
        );

        $node->setProperty(
            'jcr:predecessors',
            array($versionNode),
            PropertyType::REFERENCE,
            false
        );

        foreach ($versionNode->getPropertyValueWithDefault('jcr:predecessors', array()) as $predecessorNode) {
            /** @var NodeInterface $predecessorNode */
            $successorProperty = $predecessorNode->getProperty('jcr:successors');
            $successorProperty->addValue($versionNode);
            $predecessorNode->setModified();
        }

        $node->setProperty('jcr:isCheckedOut', false, PropertyType::BOOLEAN, false);
        $node->setProperty('jcr:baseVersion', $versionNode, PropertyType::REFERENCE, false);
        $node->setModified();
        $this->session->save();

        return $versionNode->getPath();
    }

    /**
     * Performs a checkout for the node at the given path.
     *
     * @param string $path The absolute path of the node to checkout
     *
     * @throws \Exception
     */
    public function checkoutItem($path)
    {
        $node = $this->objectManager->getNodeByPath($path);

        if ($node->isCheckedOut()) {
            return;
        }

        if (!$node->isNodeType(static::MIX_SIMPLE_VERSIONABLE)) {
            throw new UnsupportedRepositoryOperationException(
                'Node has to implement at least "mix:simpleVersionable" to use verisoning operations'
            );
        }

        $node->setProperty('jcr:isCheckedOut', true, PropertyType::BOOLEAN, false);
        $node->setModified();

        // TODO unset read only from subgraph

        // TODO add base version to predecessors

        $this->session->save();
    }

    /**
     * Attaches a nt:frozenNode copy of $node to $versionNode
     *
     * @param NodeInterface $versionNode The version to attach the nt:frozenNode to
     * @param NodeInterface $node The node to be copied into an nt:frozenNode
     */
    private function createFrozenNode(NodeInterface $versionNode, NodeInterface $node)
    {
        $frozenNode = $versionNode->addNode('jcr:frozenNode', 'nt:frozenNode');
        $frozenNode->setProperty('jcr:frozenUuid', $node->getProperty('jcr:uuid'));
        $frozenNode->setProperty('jcr:frozenPrimaryType', $node->getProperty('jcr:primaryType'));
        if ($frozenNode->hasProperty('jcr:mixinTypes')) {
            $frozenNode->setProperty('jcr:frozenMixinTypes', $node->getProperty('jcr:mixinTypes'));
        }

        foreach ($node->getProperties() as $property) {
            /** @var PropertyInterface $property */
            $propertyName = $property->getName();
            if ($propertyName == 'jcr:primaryType'
                || $propertyName == 'jcr:mixinTypes'
                || $propertyName == 'jcr:uuid'
            ) {
                continue;
            }

            $onParentVersion = $property->getDefinition()->getOnParentVersion();
            if ($onParentVersion != OnParentVersionAction::COPY && $onParentVersion != OnParentVersionAction::VERSION) {
                continue;
            }

            // TODO apply other steps based on onParentValue
            // (see step 6 3.13.9 on http://www.day.com/specs/jcr/2.0/3_Repository_Model.html)

            $frozenNode->setProperty($propertyName, $property->getValue());
        }
    }

    public function restoreItem($removeExisting, $versionPath, $path)
    {
        $node = $this->objectManager->getNodeByPath($path);

        if ($node->isModified()) {
            throw new InvalidItemStateException(
                sprintf(
                    'Node "%s" contains unsaved changes',
                    $path
                )
            );
        }

        $versionNode = $this->objectManager->getNodeByPath($versionPath, 'Version\Version');
        $frozenNode = $versionNode->getNode('jcr:frozenNode');

        // TODO reset primary type

        // TODO reset mixin types

        // TODO reset uuid

        foreach ($frozenNode->getProperties() as $property) {
            /** @var PropertyInterface $property */
            $propertyName = $property->getName();
            if ($propertyName == 'jcr:frozenPrimaryType'
                || $propertyName == 'jcr:frozenMixinTypes'
                || $propertyName == 'jcr:frozenUuid'
            ) {
                continue;
            }

            $nodeProperty = $node->getProperty($propertyName);
            $onParentVersion = $nodeProperty->getDefinition()->getOnParentVersion();
            if ($onParentVersion == OnParentVersionAction::COPY || $onParentVersion == OnParentVersionAction::VERSION) {
                $nodeProperty->setValue($property->getValue());
            }

            // TODO handle other onParentVersion cases
        }

        // TODO handle properties present on the node but not on the frozen node

        // TODO handle identifier collisions

        // TODO handle chained versions on restore

        // TODO restoring child nodes

        $node->setProperty('jcr:isCheckedOut', false, PropertyType::BOOLEAN, false);

        $this->session->save();
    }
}
