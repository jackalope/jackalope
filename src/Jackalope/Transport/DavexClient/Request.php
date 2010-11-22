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
     * Name of the request type to be used.
     * @var string
     */
    protected $type = '';

    /**
     * Instance of an implementation of the \Jackalope\Interfaces\DavexClient\Request.
     * @var \Jackalope\Interfaces\DavexClient\Request
     */
    protected $typeObject = null;

    /**
     * List of arguments to be passed to the request type object.
     * @var array
     */
    protected $arguments = array();

    /**
     * Constructor of the class.
     *
     * @param string $type Name of the request to be handled.
     */
    public function __construct($type, array $arguments) {
        $this->type = $type;
        $this->arguments = $arguments;
    }

    /**
     * Wrapper to expose the request specific build method via the request object.
     * @return null
     */
    public function build() {
        $this->getTypeObject()->build();
    }

    /*************************************************************************/
    /* Depedencies
    /*************************************************************************/

    /**
     * Instantiates a DOMDocument object if not existant.
     *
     * @return DOMDocument
     */
    protected function getDomObject() {
        if ($this->dom === NULL) {
            $this->dom = new \DOMDocument('1.0', 'UTF-8');
        }
        return $this->dom;
    }

    /**
     * Instantiates a type specific request object.
     *
     * @return Jackalope\Interfaces\DavexClient\Request
     * @throws \InvalidArgumentException
     */
    protected function getTypeObject() {
        if (is_null($this->typeObject)) {
            switch($this->type) {
                case 'NodeTypes':
                    $this->typeObject = new \Jackalope\Transport\DavexClient\Requests\NodeTypes(
                        $this->getDomObject(),
                        $this->arguments
                    );
                    break;
                default:
                    throw new \InvalidArgumentException('Invalid request type ('.$this->type.').');
            }
        }
        return $this->typeObject;
    }

    /**
     * Sets the request object ot be used instead of the original one.
     *
     * @param \Jackalope\Interfaces\DavexClient\Request $request
     */
    public function setTypeObject(\Jackalope\Interfaces\DavexClient\Request $request) {
        $this->typeObject = $request;
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
        return strval($this->getTypeObject());
    }
}