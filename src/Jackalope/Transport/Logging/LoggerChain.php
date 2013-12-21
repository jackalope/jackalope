<?php

namespace Jackalope\Transport\Logging;

/**
 * Chains multiple LoggerInterface.
 *
 * @author Christophe Coevoet <stof@notk.org>
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 */
class LoggerChain implements LoggerInterface
{
    /**
     * @var \Jackalope\Transport\Logging\LoggerInterface[]
     */
    private $loggers = array();

    /**
     * Adds a logger in the chain.
     *
     * @param \Jackalope\Transport\Logging\LoggerInterface $logger
     *
     * @return void
     */
    public function addLogger(LoggerInterface $logger)
    {
        $this->loggers[] = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function startCall($method, array $params = null, array $env = null)
    {
        foreach ($this->loggers as $logger) {
            $logger->startCall($method, $params, $env);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function stopCall()
    {
        foreach ($this->loggers as $logger) {
            $logger->stopCall();
        }
    }
}
