<?php

namespace Jackalope\Transport\Logging;

/**
 * Includes executed calls in a Debug Stack.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author Jonathan Wage <jonwage@gmail.com>
 * @author Roman Borschel <roman@code-factory.org>
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 */
class DebugStack implements LoggerInterface
{
    /**
     * Executed calls.
     *
     * @var array
     */
    public $calls = array();

    /**
     * If the logger is enabled (log calls) or not.
     *
     * @var boolean
     */
    public $enabled = true;

    /**
     * @var float|null
     */
    public $start = null;

    /**
     * @var integer
     */
    public $currentQuery = 0;

    /**
     * {@inheritdoc}
     */
    public function startCall($method, array $params = null, array $env = null)
    {
        if ($this->enabled) {
            $this->start = microtime(true);
            $this->calls[++$this->currentQuery] = array('method' => $method, 'params' => $params, 'env' => $env, 'executionMS' => 0);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function stopCall()
    {
        if ($this->enabled) {
            $this->calls[$this->currentQuery]['executionMS'] = microtime(true) - $this->start;
        }
    }
}
