<?php
/**
 * Wrapper class to abstract the curl* PHP userland functions.
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
namespace Jackalope\Transport;

/**
 * Capsulate curl as an object
 *
 * @todo: TODO: Write phpunit tests
 *
 * @package jackalope
 * @subpackage transport
 */
class curl
{
    /**
     * Contains a connection resource to a curl session.
     * @var resource
     */
    protected $curl;

    /**
     * Contains header of a response, if needed
     * @var array
     */

    protected $headers = array();

    /**
     * Response body as a string
     * @var string
     */
    protected $response = '';

    /**
     * Handles the initialization of a curl session.
     *
     * @param string $url If provided, sets the CURLOPT_URL
     *
     * @see curl_init
     */
    public function __construct($url = null)
    {
        $this->curl = curl_init($url);
    }

    /**
     * Sets the options to be used for the request.
     *
     * @param int $option
     * @param mixed $value
     *
     * @see curl_setopt
     */
    public function setopt($option, $value)
    {
        return curl_setopt($this->curl, $option, $value);
    }

    /**
     * Sets multiple options to be used for a request.
     *
     * @param array $options
     *
     * @see curl_setopt_array
     */
    public function setopt_array($options)
    {
        return curl_setopt_array($this->curl, $options);
    }

    /**
     * Performs a cUrl session.
     *
     * @return bool false on failure otherwise true or string (if CURLOPT_RETURNTRANSFER option is set)
     *
     * @see curl_exec
     */
    public function exec()
    {
        return curl_exec($this->curl);
    }

    /**
     * Gets the last error for the current cUrl session.
     *
     * @return string
     *
     * @see curl_error
     */
    public function error()
    {
        return curl_error($this->curl);
    }

    /**
     * Gets the number representation of the last error for the current cUrl session.
     *
     * @return int
     *
     * @see curl_errno
     */
    public function errno()
    {
        return curl_errno($this->curl);
    }

    /**
     * Gets information regarding a specific transfer.
     *
     * @param int $option {@link http://ch.php.net/manual/en/function.curl-getinfo.php} to find a list of possible options.
     * @return string|array Returns a string if options is given otherwise associative array
     *
     * @see curl_getinfo
     */
    public function getinfo($option = null)
    {
        return curl_getinfo($this->curl, $option);
    }

    /**
     * Closes the current cUrl session.
     *
     * @return void
     *
     * @see curl_close
     */
    public function close()
    {
        return curl_close($this->curl);
    }

    public function readHeader($ch, $header)
    {
        if (strpos($header,":") !== false) {
            list($key,$value) = explode(":",$header,2);
            $this->headers[$key] = trim($value);
        }
        return strlen($header);
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getHeader($key)
    {
        if (isset($this->headers[$key])) {
            return $this->headers[$key];
        }
        return null;
    }

    public function parseResponseHeaders()
    {
        $this->setopt(CURLOPT_HEADER, false);
        $this->setopt(CURLOPT_HEADERFUNCTION, array(&$this,'readHeader'));
    }

    public function setResponse($r)
    {
        $this->response = $r;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getCurl()
    {
        return $this->curl;
    }

}
