<?php

namespace Jackalope\Transport\Logging;

use Psr\Log\LoggerInterface as Psr3LoggerInterface;

/**
 * Logs to a PSR-3 Logger.
 *
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 */
class Psr3Logger implements LoggerInterface
{
    const MAX_STRING_LENGTH = 32;
    const BINARY_DATA_VALUE = '(binary value)';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * If the logger is enabled (log calls) or not.
     *
     * @var boolean
     */
    public $enabled = true;

    /**
     * Constructor.
     *
     * @param Psr3LoggerInterface $logger    A logger instance
     */
    public function __construct(Psr3LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function startCall($method, array $params = null, array $env = null)
    {
        if ($this->enabled && $this->logger) {
            if (is_array($params)) {
                foreach ($params as $index => $param) {
                    if (!is_string($params[$index])) {
                        continue;
                    }

                    // non utf-8 strings break json encoding
                    if (!preg_match('#[\p{L}\p{N} ]#u', $params[$index])) {
                        $params[$index] = self::BINARY_DATA_VALUE;
                        continue;
                    }

                    // detect if the too long string must be shorten
                    if (function_exists('mb_detect_encoding') && false !== $encoding = mb_detect_encoding($params[$index])) {
                        if (self::MAX_STRING_LENGTH < mb_strlen($params[$index], $encoding)) {
                            $params[$index] = mb_substr($params[$index], 0, self::MAX_STRING_LENGTH - 6, $encoding).' [...]';
                            continue;
                        }
                    } else {
                        if (self::MAX_STRING_LENGTH < strlen($params[$index])) {
                            $params[$index] = substr($params[$index], 0, self::MAX_STRING_LENGTH - 6).' [...]';
                            continue;
                        }
                    }
                }
            }

            $this->logger->info($method, array('params' => $params, 'env' => $env));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function stopCall()
    {
    }
}
