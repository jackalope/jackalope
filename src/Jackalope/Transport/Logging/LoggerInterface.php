<?php

namespace Jackalope\Transport\Logging;

/**
 * Interface for transport loggers.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author Jonathan Wage <jonwage@gmail.com>
 * @author Roman Borschel <roman@code-factory.org>
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 */
interface LoggerInterface
{
    /**
     * Logs a call statement somewhere.
     *
     * @param string     $method the call to be executed
     * @param array|null $params the call parameters
     * @param array|null $env    associative array with environment information
     *
     * @return void
     */
    public function startCall($method, array $params = null, array $env = null);

    /**
     * Marks the last started query as stopped. This can be used for timing of queries.
     *
     * @return void
     */
    public function stopCall();
}
