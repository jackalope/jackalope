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
     */
    public function startCall(string $method, ?array $params = null, ?array $env = null): void;

    /**
     * Marks the last started query as stopped. This can be used for timing of queries.
     */
    public function stopCall(): void;
}
