<?php
/**
 * Base class to handle the communication between Jackalope and Jackrabbit via Davex.
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

namespace Jackalope\Transport\DavexClient\Requests;

/**
 * Base class for each request to depend on.
 *
 * @package jackalope
 * @subpackage transport
 */
abstract class Base implements \Jackalope\Interfaces\DavexClient\Request
{
    /**
     * String representation of the request.
     * @var string
     */
    protected $xml = '<?xml version="1.0" encoding="UTF-8"?>';

    /**
     * List of arguments to be handled.
     * @var array
     */
    protected $arguments = array();

    /*************************************************************************/
    /* Preimplementations
    /*************************************************************************/

    /**
     * Initiaties the NodeTypes request object.
     *
     * @param array $arguments
     */
    public function __construct(array $arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * Returns the built xml.
     *
     * @return string The XML string representation of the recent generated request.
     */
    public function getXml()
    {
        return $this->xml;
    }

    /*************************************************************************/
    /* Abstract methods
    /*************************************************************************/

    abstract public function build();

    /*************************************************************************/
    /* Magic methods
    /*************************************************************************/

    /**
     * Wrapper for getXml().
     *
     * @return string The XML string representation of the recent generated request.
     */
    public function __toString()
    {
        return $this->getXml();
    }
}