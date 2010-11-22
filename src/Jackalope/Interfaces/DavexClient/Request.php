<?php

namespace Jackalope\Interfaces\DavexClient;

interface Request {

    /**
     * Identifier of the 'GET' http request method.
     * @var string
     */
    const GET = 'GET';

    /**
     * Identifier of the 'REPORT' http request method.
     * @var string
     */
    const REPORT = 'REPORT';

    /**
     * Identifier of the 'PROPFIND' http request method.
     * @var string
     */
    const PROPFIND = 'PROPFIND';

    /**
     * Identifier of the 'PROPPATCH' http request method.
     * @var string
     */
    const PROPPATCH = 'PROPPATCH';

    /**
     * Generates the DOMDocument representing the request.
     *
     * @return null
     */
    public function build();

    /**
     * Generates the XML string representing the request.
     *
     * @return string
     */
    public function getXML();

    /**
     * Generates the XML string representing the request.
     *
     * @return string
     */
    public function __toString();
}