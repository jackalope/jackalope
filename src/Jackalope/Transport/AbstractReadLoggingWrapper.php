<?php

namespace Jackalope\Transport;

use Jackalope\FactoryInterface;
use Jackalope\Transport\Logging\LoggerInterface;
use PHPCR\CredentialsInterface;

use Jackalope\NodeType\NodeTypeManager;

/**
 * abstract class for logging transport wrapper.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 */

abstract class AbstractReadLoggingWrapper implements TransportInterface
{
    /**
     * @var TransportInterface
     */
    protected $transport;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param FactoryInterface   $factory
     * @param TransportInterface $transport   A logger instance
     * @param LoggerInterface    $logger    A logger instance
     */
    public function __construct(FactoryInterface $factory, TransportInterface $transport, LoggerInterface $logger)
    {
        $this->transport = $transport;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function getRepositoryDescriptors()
    {
        return $this->transport->getRepositoryDescriptors();
    }

    /**
     * {@inheritDoc}
     */
    public function getAccessibleWorkspaceNames()
    {
        return $this->transport->getAccessibleWorkspaceNames();
    }

    /**
     * {@inheritDoc}
     */
    public function login(CredentialsInterface $credentials = null, $workspaceName = null)
    {
        return $this->transport->login($credentials, $workspaceName);
    }

    /**
     * {@inheritDoc}
     */
    public function logout()
    {
        $this->transport->logout();
    }

    /**
     * {@inheritDoc}
     */
    public function getNamespaces()
    {
        return $this->transport->getNamespaces();
    }

    /**
     * {@inheritDoc}
     */
    public function getNode($path)
    {
        $this->logger->startCall(__FUNCTION__, func_get_args(), array('fetchDepth' => $this->transport->getFetchDepth()));
        $result = $this->transport->getNode($path);
        $this->logger->stopCall();
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getNodes($paths)
    {
        $this->logger->startCall(__FUNCTION__, func_get_args(), array('fetchDepth' => $this->transport->getFetchDepth()));
        $result = $this->transport->getNodes($paths);
        $this->logger->stopCall();
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getNodesByIdentifier($identifiers)
    {
        $this->logger->startCall(__FUNCTION__, func_get_args(), array('fetchDepth' => $this->transport->getFetchDepth()));
        $result = $this->transport->getNodesByIdentifier($identifiers);
        $this->logger->stopCall();
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getProperty($path)
    {
        $this->logger->startCall(__FUNCTION__, func_get_args(), array('fetchDepth' => $this->transport->getFetchDepth()));
        $result = $this->transport->getProperty($path);
        $this->logger->stopCall();
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getNodeByIdentifier($uuid)
    {
        $this->logger->startCall(__FUNCTION__, func_get_args(), array('fetchDepth' => $this->transport->getFetchDepth()));
        $result = $this->transport->getNodeByIdentifier($uuid);
        $this->logger->stopCall();
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getNodePathForIdentifier($uuid, $workspace = null)
    {
        $this->logger->startCall(__FUNCTION__, func_get_args());
        $result = $this->transport->getNodePathForIdentifier($uuid, $workspace);
        $this->logger->stopCall();
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getBinaryStream($path)
    {
        $this->logger->startCall(__FUNCTION__, func_get_args());
        $result = $this->transport->getBinaryStream($path);
        $this->logger->stopCall();
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getReferences($path, $name = null)
    {
        $this->logger->startCall(__FUNCTION__, func_get_args());
        $result = $this->transport->getReferences($path, $name);
        $this->logger->stopCall();
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getWeakReferences($path, $name = null)
    {
        $this->logger->startCall(__FUNCTION__, func_get_args());
        $result = $this->transport->getWeakReferences($path, $name);
        $this->logger->stopCall();
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function setNodeTypeManager($nodeTypeManager)
    {
        return $this->transport->setNodeTypeManager($nodeTypeManager);
    }

    /**
     * {@inheritDoc}
     */
    public function getNodeTypes($nodeTypes = array())
    {
        $this->logger->startCall(__FUNCTION__, func_get_args());
        $result = $this->transport->getNodeTypes($nodeTypes);
        $this->logger->stopCall();
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function setFetchDepth($depth)
    {
        $this->transport->setFetchDepth($depth);
    }

    /**
     * {@inheritDoc}
     */
    public function getFetchDepth()
    {
        return $this->transport->getFetchDepth();
    }

    /**
     * {@inheritDoc}
     */
    public function setAutoLastModified($autoLastModified)
    {
        return $this->transport->setAutoLastModified($autoLastModified);
    }

    /**
     * {@inheritDoc}
     */
    public function getAutoLastModified()
    {
        return $this->transport->getAutoLastModified();
    }

}
