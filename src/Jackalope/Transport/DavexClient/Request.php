<?php
/**
 * Class to handle Davex requests.
 *
 * @package jackalope
 * @subpackage transport
 */

namespace Jackalope\Transport\DavexClient;

/**
 * Class to handle Davex requests.
 *
 * @package jackalope
 * @subpackage transport
 */
class Request {

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
     * DOM representation of the request.
     * @var DOMDocument
     */
    protected $dom = null;

    /*************************************************************************/
    /* Depedencies
    /*************************************************************************/

    /**
     * Instantiates an DOMDocument object if not existant.
     *
     * @return DOMDocument
     */
    protected function getDomObject() {
        if ($this->dom === NULL) {
            $this->dom = new \DOMDocument();
        }
        return $this->dom;
    }


    /*************************************************************************/
    /* magic methods
    /*************************************************************************/

    /**
     * Generates the XML string representation of the request.
     *
     * @return string XML representation of the request.
     */
    public function __toString() {
        return "<xml />";
    }
}