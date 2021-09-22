<?php

namespace Jackalope\Transport\Logging;

/**
 * Chains multiple LoggerInterface.
 *
 * @author Christophe Coevoet <stof@notk.org>
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 */
final class LoggerChain implements LoggerInterface
{
    /**
     * @var LoggerInterface[]
     */
    private array $loggers = [];

    public function addLogger(LoggerInterface $logger): void
    {
        $this->loggers[] = $logger;
    }

    public function startCall(string $method, array $params = null, array $env = null): void
    {
        foreach ($this->loggers as $logger) {
            $logger->startCall($method, $params, $env);
        }
    }

    public function stopCall(): void
    {
        foreach ($this->loggers as $logger) {
            $logger->stopCall();
        }
    }
}
