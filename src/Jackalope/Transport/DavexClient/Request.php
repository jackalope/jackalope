<?php
/**
 * Class to handle Davex requests.
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache License Version 2.0, January 2004
 *   Licensed under the Apache License, Version 2.0 (the "License");
 *   you may not use this file except in compliance with the License.
 *   You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 *   Unless required by applicable law or agreed to in writing, software
 *   distributed under the License is distributed on an "AS IS" BASIS,
 *   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *   See the License for the specific language governing permissions and
 *   limitations under the License.
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
     * DOM representation of the request.
     * @var DOMDocument
     */
    protected $dom = null;

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
            $this->dom = new \DOMDocument('1.0', 'UTF-8');
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