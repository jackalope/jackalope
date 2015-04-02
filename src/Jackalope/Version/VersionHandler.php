<?php

namespace Jackalope\Version;

use Jackalope\Node;
use Jackalope\Transport\TransportInterface;
use PHPCR\UnsupportedRepositoryOperationException;

class VersionHandler
{
    const MIX_VERSIONABLE = 'mix:versionable';
    const MIX_SIMPLE_VERSIONABLE = 'mix:simpleVersionable';

    /**
     * @var TransportInterface
     */
    private $transport;

    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    public static function addVersionProperties(Node $node, TransportInterface $transport)
    {
        // TODO solve without reflection
        $reflection = new \ReflectionMethod(get_class($transport), 'storeNode');
        $reflection->setAccessible(true);

        // TODO move this to the version handler
        $session = $node->getSession();
        $rootNode = $session->getRootNode();

        $node->setProperty('jcr:isCheckedOut', true);

        if (!$rootNode->hasNode('jcr:system/jcr:versionStorage')) {
            if (!$rootNode->hasNode('jcr:system')) {
                $rootNode->addNode('jcr:system');
            }
            $rootNode->addNode('jcr:system/jcr:versionStorage');
        }

        $versionStorageNode = $rootNode->getNode('jcr:system/jcr:versionStorage');
        if (!$versionStorageNode->hasNode($node->getIdentifier())) {
            $versionStorageNode->addNode($node->getIdentifier(), 'nt:versionHistory');
        }
        $versionHistory = $versionStorageNode->getNode($node->getIdentifier());
        $versionHistory->setProperty('jcr:versionableUuid', $node->getIdentifier());

        // TODO Set jcr:copiedFrom if needed

        $versionHistory->addNode('jcr:versionLabels', 'nt:versionLabels');
        $rootVersion = $versionHistory->addNode('jcr:rootVersion', 'nt:version');
        $reflection->invoke($transport, $rootVersion->getPath(), $rootVersion->getProperties());
        $rootVersion->setClean();

        // TODO add frozen node to root version

        $reflection->invoke($transport, $versionHistory->getPath(), $versionHistory->getProperties());
        $versionHistory->setClean();

        $node->setProperty('jcr:versionHistory', $versionHistory);
        $node->setProperty('jcr:baseVersion', $rootVersion); // TODO set correct base version
        $node->setProperty('jcr:predecessors', array($rootVersion));
    }
}
