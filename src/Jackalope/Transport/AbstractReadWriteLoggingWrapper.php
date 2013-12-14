<?php

namespace Jackalope\Transport;

use Jackalope\Node;

/**
 * abstract class for logging transport wrapper.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 *
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 */

abstract class AbstractReadWriteLoggingWrapper extends AbstractReadLoggingWrapper implements WritingInterface
{
    /**
     * {@inheritDoc}
     */
    public function assertValidName($name)
    {
        return $this->transport->assertValidName($name);
    }

    /**
     * {@inheritDoc}
     */
    public function copyNode($srcAbsPath, $destAbsPath, $srcWorkspace = null)
    {
        return $this->transport->copyNode($srcAbsPath, $destAbsPath, $srcWorkspace);
    }

    /**
     * {@inheritDoc}
     */
    public function cloneFrom($srcWorkspace, $srcAbsPath, $destAbsPath, $removeExisting)
    {
        return $this->transport->cloneFrom($srcWorkspace, $srcAbsPath, $destAbsPath, $removeExisting);
    }

    /**
     * {@inheritDoc}
     */
    public function updateNode(Node $node, $srcWorkspace)
    {
        return $this->transport->updateNode($node, $srcWorkspace);
    }

    /**
     * {@inheritDoc}
     */
    public function moveNodes(array $operations)
    {
        return $this->transport->moveNodes($operations);
    }

    /**
     * {@inheritDoc}
     */
    public function moveNodeImmediately($srcAbsPath, $dstAbsPath)
    {
        return $this->transport->moveNodeImmediately($srcAbsPath, $dstAbsPath);
    }

    /**
     * {@inheritDoc}
     */
    public function reorderChildren(Node $node)
    {
        return $this->transport->reorderChildren($node);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteNodes(array $operations)
    {
        return $this->transport->deleteNodes($operations);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteProperties(array $operations)
    {
        return $this->transport->deleteProperties($operations);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteNodeImmediately($path)
    {
        return $this->transport->deleteNodeImmediately($path);
    }

    /**
     * {@inheritDoc}
     */
    public function deletePropertyImmediately($path)
    {
        return $this->transport->deletePropertyImmediately($path);
    }

    /**
     * {@inheritDoc}
     */
    public function storeNodes(array $operations)
    {
        return $this->transport->storeNodes($operations);
    }

    /**
     * {@inheritDoc}
     */
    public function updateProperties(Node $node)
    {
        return $this->transport->updateProperties($node);
    }

    /**
     * {@inheritDoc}
     */
    public function registerNamespace($prefix, $uri)
    {
        return $this->transport->registerNamespace($prefix, $uri);
    }

    /**
     * {@inheritDoc}
     */
    public function unregisterNamespace($prefix)
    {
        return $this->transport->unregisterNamespace($prefix);
    }

    /**
     * {@inheritDoc}
     */
    public function prepareSave()
    {
        return $this->transport->prepareSave();
    }

    /**
     * {@inheritDoc}
     */
    public function finishSave()
    {
        return $this->transport->finishSave();
    }

    /**
     * {@inheritDoc}
     */
    public function rollbackSave()
    {
        return $this->transport->rollbackSave();
    }
}
