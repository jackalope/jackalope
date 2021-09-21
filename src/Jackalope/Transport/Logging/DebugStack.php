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
    public $calls = [];

    /**
     * If the logger is enabled (log calls) or not.
     *
     * @var bool
     */
    public $enabled = true;

    /**
     * Show the debug backtrace for each call.
     *
     * @var bool
     */
    public $backtrace = false;

    /**
     * @var float|null
     */
    public $start = null;

    /**
     * @var int
     */
    public $currentQuery = 0;

    /**
     * {@inheritdoc}
     */
    public function startCall($method, array $params = null, array $env = null)
    {
        if ($this->enabled) {
            $this->start = microtime(true);
            $call = ['method' => $method, 'params' => $params, 'env' => $env, 'executionMS' => 0];

            if (true === $this->backtrace) {
                $call['caller'] = $this->getBacktrace();
            }

            $this->calls[++$this->currentQuery] = $call;
        }
    }

    public function enableBacktrace()
    {
        $this->backtrace = true;
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

    /**
     * Return a simple backtrace showing, for each caller, the class, function and line number.
     *
     * @return array
     */
    private function getBacktrace()
    {
        $fullBacktrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        $backtrace = [];
        foreach ($fullBacktrace as $trace) {
            $string = '';
            if (isset($trace['class'])) {
                $string .= $trace['class'];
            }

            if (isset($trace['function'])) {
                $string .= '->'.$trace['function'];
            }

            if (isset($trace['line'])) {
                $string .= '#'.$trace['line'];
            }

            $backtrace[] = $string;
        }

        return $backtrace;
    }
}
