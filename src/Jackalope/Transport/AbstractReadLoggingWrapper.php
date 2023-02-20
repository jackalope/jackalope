<?php

namespace Jackalope\Transport;

use Jackalope\FactoryInterface;
use Jackalope\NodeType\NodeTypeManager;
use Jackalope\Transport\Logging\LoggerInterface;
use PHPCR\CredentialsInterface;

/**
 * abstract class for logging transport wrapper.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 */
abstract class AbstractReadLoggingWrapper implements TransportInterface
{
    protected TransportInterface $transport;
    protected LoggerInterface $logger;

    public function __construct(FactoryInterface $factory, TransportInterface $transport, LoggerInterface $logger)
    {
        $this->transport = $transport;
        $this->logger = $logger;
    }

    public function getRepositoryDescriptors(): array
    {
        return $this->transport->getRepositoryDescriptors();
    }

    public function getAccessibleWorkspaceNames(): array
    {
        return $this->transport->getAccessibleWorkspaceNames();
    }

    public function login(CredentialsInterface $credentials = null, $workspaceName = null): string
    {
        return $this->transport->login($credentials, $workspaceName);
    }

    public function logout(): void
    {
        $this->transport->logout();
    }

    public function getNamespaces(): array
    {
        return $this->transport->getNamespaces();
    }

    public function getNode(string $path): \stdClass
    {
        $this->logger->startCall(__FUNCTION__, func_get_args(), ['fetchDepth' => $this->transport->getFetchDepth()]);
        $result = $this->transport->getNode($path);
        $this->logger->stopCall();

        return $result;
    }

    public function getNodes($paths): array
    {
        $this->logger->startCall(__FUNCTION__, func_get_args(), ['fetchDepth' => $this->transport->getFetchDepth()]);
        $result = $this->transport->getNodes($paths);
        $this->logger->stopCall();

        return $result;
    }

    public function getNodesByIdentifier($identifiers): array
    {
        $this->logger->startCall(__FUNCTION__, func_get_args(), ['fetchDepth' => $this->transport->getFetchDepth()]);
        $result = $this->transport->getNodesByIdentifier($identifiers);
        $this->logger->stopCall();

        return $result;
    }

    public function getProperty($path): \stdClass
    {
        $this->logger->startCall(__FUNCTION__, func_get_args(), ['fetchDepth' => $this->transport->getFetchDepth()]);
        $result = $this->transport->getProperty($path);
        $this->logger->stopCall();

        return $result;
    }

    public function getNodeByIdentifier(string $uuid): \stdClass
    {
        $this->logger->startCall(__FUNCTION__, func_get_args(), ['fetchDepth' => $this->transport->getFetchDepth()]);
        $result = $this->transport->getNodeByIdentifier($uuid);
        $this->logger->stopCall();

        return $result;
    }

    public function getNodePathForIdentifier($uuid, $workspace = null): string
    {
        $this->logger->startCall(__FUNCTION__, func_get_args());
        $result = $this->transport->getNodePathForIdentifier($uuid, $workspace);
        $this->logger->stopCall();

        return $result;
    }

    public function getBinaryStream($path)
    {
        $this->logger->startCall(__FUNCTION__, func_get_args());
        $result = $this->transport->getBinaryStream($path);
        $this->logger->stopCall();

        return $result;
    }

    public function getReferences($path, $name = null): array
    {
        $this->logger->startCall(__FUNCTION__, func_get_args());
        $result = $this->transport->getReferences($path, $name);
        $this->logger->stopCall();

        return $result;
    }

    public function getWeakReferences($path, $name = null): array
    {
        $this->logger->startCall(__FUNCTION__, func_get_args());
        $result = $this->transport->getWeakReferences($path, $name);
        $this->logger->stopCall();

        return $result;
    }

    public function setNodeTypeManager(NodeTypeManager $nodeTypeManager): void
    {
        $this->transport->setNodeTypeManager($nodeTypeManager);
    }

    public function getNodeTypes($nodeTypes = []): array
    {
        $this->logger->startCall(__FUNCTION__, func_get_args());
        $result = $this->transport->getNodeTypes($nodeTypes);
        $this->logger->stopCall();

        return $result;
    }

    public function setFetchDepth($depth): void
    {
        $this->transport->setFetchDepth($depth);
    }

    public function getFetchDepth(): int
    {
        return $this->transport->getFetchDepth();
    }

    public function setAutoLastModified(bool $autoLastModified): void
    {
        $this->transport->setAutoLastModified($autoLastModified);
    }

    public function getAutoLastModified(): bool
    {
        return $this->transport->getAutoLastModified();
    }
}
