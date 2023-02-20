<?php

namespace Jackalope\Transport;

use Jackalope\Node;

/**
 * Abstract class for logging transport wrapper.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 */
abstract class AbstractReadWriteLoggingWrapper extends AbstractReadLoggingWrapper implements WritingInterface
{
    public function assertValidName($name): bool
    {
        return $this->transport->assertValidName($name);
    }

    public function copyNode($srcAbsPath, $destAbsPath, $srcWorkspace = null): void
    {
        $this->transport->copyNode($srcAbsPath, $destAbsPath, $srcWorkspace);
    }

    public function cloneFrom($srcWorkspace, $srcAbsPath, $destAbsPath, $removeExisting): void
    {
        $this->transport->cloneFrom($srcWorkspace, $srcAbsPath, $destAbsPath, $removeExisting);
    }

    public function updateNode(Node $node, $srcWorkspace): void
    {
        $this->transport->updateNode($node, $srcWorkspace);
    }

    public function moveNodes(array $operations): void
    {
        $this->transport->moveNodes($operations);
    }

    public function moveNodeImmediately($srcAbsPath, $dstAbsPath): void
    {
        $this->transport->moveNodeImmediately($srcAbsPath, $dstAbsPath);
    }

    public function reorderChildren(Node $node): void
    {
        $this->transport->reorderChildren($node);
    }

    public function deleteNodes(array $operations): void
    {
        $this->transport->deleteNodes($operations);
    }

    public function deleteProperties(array $operations): void
    {
        $this->transport->deleteProperties($operations);
    }

    public function deleteNodeImmediately($path): void
    {
        $this->transport->deleteNodeImmediately($path);
    }

    public function deletePropertyImmediately($path): void
    {
        $this->transport->deletePropertyImmediately($path);
    }

    public function storeNodes(array $operations): void
    {
        $this->transport->storeNodes($operations);
    }

    public function updateProperties(Node $node): void
    {
        $this->transport->updateProperties($node);
    }

    public function registerNamespace($prefix, $uri): void
    {
        $this->transport->registerNamespace($prefix, $uri);
    }

    public function unregisterNamespace($prefix): void
    {
        $this->transport->unregisterNamespace($prefix);
    }

    public function prepareSave(): void
    {
        $this->transport->prepareSave();
    }

    public function finishSave(): void
    {
        $this->transport->finishSave();
    }

    public function rollbackSave(): void
    {
        $this->transport->rollbackSave();
    }
}
