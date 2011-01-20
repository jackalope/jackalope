<?php
/**
 * Request class for the Davex protocol
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

namespace Jackalope\Transport\Davex;

/**
 * Request class for the Davex protocol
 *
 * @package jackalope
 * @subpackage transport
 */
class Request
{
    /**
     * Name of the user agent to be exposed to a client.
     * @var string
     */
    const USER_AGENT = 'jackalope-php/1.0';

    /**
     * Identifier of the 'GET' http request method.
     * @var string
     */
    const GET = 'GET';

    /**
     * Identifier of the 'PUT' http request method.
     * @var string
     */
    const PUT = 'PUT';

    /**
     * Identifier of the 'MKCOL' http request method.
     * @var string
     */
    const MKCOL = 'MKCOL';

    /**
     * Identifier of the 'DELETE' http request method.
     * @var string
     */
    const DELETE = 'DELETE';

    /**
     * Identifier of the 'REPORT' http request method.
     * @var string
     */
    const REPORT = 'REPORT';

    /**
     * Identifier of the 'SEARCH' http request method.
     * @var string
     */
    const SEARCH = 'SEARCH';

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
     * Identifier of the 'COPY' http request method.
     * @var string
     */
    const COPY = 'COPY';

    /**
     * Identifier of the 'MOVE' http request method.
     * @var string
     */
    const MOVE = 'MOVE';

    /**
     * Identifier of the 'CHECKIN' http request method.
     * @var string
     */
    const CHECKIN = 'CHECKIN';

    /**
     * Identifier of the 'CHECKOUT' http request method.
     * @var string
     */
    const CHECKOUT = 'CHECKOUT';

    /**
     * Identifier of the 'UPDATE' http request method.
     * @var string
     */
    const UPDATE = 'UPDATE';

    /** @var string     Possible argument for {@link setDepth()} */
    const INFINITY = 'infinity';

    /**
     * @var \Jackalope\Transport\curl
     */
    protected $curl;

    /**
     * Name of the request method to be used.
     * @var string
     */
    protected $method;

    /**
     * Url to get/post/..
     * @var string
     */
    protected $uri;

    /**
     * Set of credentials necessary to connect to the server or else.
     * @var \PHPCR\CredentialsInterface
     */
    protected $credentials;

    /**
     * Request content-type
     * @var string
     */
    protected $contentType = 'text/xml; charset=utf-8';

    /**
     * How far the request should go, default is 0
     * @var int
     */
    protected $depth = 0;

    /**
     * Posted content for methods that require it
     * @var string
     */
    protected $body = '';

    /** @var array[]string  A list of additional HTTP headers to be sent */
    protected $additionalHeaders = array();

    /**
     * Initiaties the NodeTypes request object.
     *
     * @param object $factory Ignored for now, as this class does not create objects
     * TODO: document other parameters
     */
    public function __construct($factory, $curl, $method, $uri)
    {
        $this->curl = $curl;
        $this->method = $method;
        $this->uri = $uri;
    }

    public function setCredentials($creds)
    {
        $this->credentials = $creds;
    }

    public function setContentType($contentType)
    {
        $this->contentType = (string) $contentType;
    }

    /**
     * @param   int|string  $depth
     */
    public function setDepth($depth)
    {
        $this->depth = $depth;
    }

    public function setBody($body)
    {
        $this->body = (string) $body;
    }

    public function setMethod($method)
    {
        $this->method = $method;
    }

    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    public function addHeader($header)
    {
        $this->additionalHeaders[] = $header;
    }

    /**
     * Requests the data to be identified by a formerly prepared request.
     *
     * Prepares the curl object, executes it and checks
     * for transport level errors, throwing the appropriate exceptions.
     *
     * @return string XML representation of the response.
     *
     * @throws \PHPCR\NoSuchWorkspaceException if it was not possible to reach the server (resolve host or connect)
     * @throws \PHPCR\ItemNotFoundException if the object was not found
     * @throws \PHPCR\RepositoryExceptions if on any other error.
     * @throws \PHPCR\PathNotFoundException if the path was not found (server returned 404 without xml response)
     *
     * @uses curl::errno()
     * @uses curl::exec()
     */
    public function execute($getCurlObject = false)
    {
        if ($this->credentials instanceof \PHPCR\SimpleCredentials) {
            $this->curl->setopt(CURLOPT_USERPWD, $this->credentials->getUserID().':'.$this->credentials->getPassword());
        } else {
            $this->curl->setopt(CURLOPT_USERPWD, null);
        }

        $headers = array(
            'Depth: ' . $this->depth,
            'Content-Type: '.$this->contentType,
            'User-Agent: '.self::USER_AGENT
        );
        $headers = array_merge($headers, $this->additionalHeaders);

        $this->curl->setopt(CURLOPT_RETURNTRANSFER, true);
        $this->curl->setopt(CURLOPT_CUSTOMREQUEST, $this->method);
        $this->curl->setopt(CURLOPT_URL, $this->uri);
        $this->curl->setopt(CURLOPT_HTTPHEADER, $headers);
        $this->curl->setopt(CURLOPT_POSTFIELDS, $this->body);
        if ($getCurlObject) {
            $this->curl->parseResponseHeaders();
        }


        $response = $this->curl->exec();

        $httpCode = $this->curl->getinfo(CURLINFO_HTTP_CODE);
        if ($httpCode >= 200 && $httpCode < 300) {
            if ($getCurlObject) {
                return $this->curl;
            } else {
                return $response;
            }
        }

        switch ($this->curl->errno()) {
        case CURLE_COULDNT_RESOLVE_HOST:
        case CURLE_COULDNT_CONNECT:
            throw new \PHPCR\NoSuchWorkspaceException($this->curl->error());
        }

        // TODO extract HTTP status string from response, more descriptive about error

        // use XML error response if it's there
        if (substr($response, 0, 1) === '<') {
            $dom = new \DOMDocument();
            $dom->loadXML($response);
            $err = $dom->getElementsByTagNameNS(Client::NS_DCR, 'exception');
            if ($err->length > 0) {
                $err = $err->item(0);
                $errClass = $err->getElementsByTagNameNS(Client::NS_DCR, 'class')->item(0)->textContent;
                $errMsg = $err->getElementsByTagNameNS(Client::NS_DCR, 'message')->item(0)->textContent;

                $exceptionMsg = 'HTTP ' . $httpCode . ': ' . $errMsg;
                switch($errClass) {
                    case 'javax.jcr.NoSuchWorkspaceException':
                        throw new \PHPCR\NoSuchWorkspaceException($exceptionMsg);
                    case 'javax.jcr.nodetype.NoSuchNodeTypeException':
                        throw new \PHPCR\NodeType\NoSuchNodeTypeException($exceptionMsg);
                    case 'javax.jcr.ItemNotFoundException':
                        throw new \PHPCR\ItemNotFoundException($exceptionMsg);
                    case 'javax.jcr.nodetype.ConstraintViolationException':
                        throw new \PHPCR\NodeType\ConstraintViolationException($exceptionMsg);

                    //TODO: map more errors here?
                    default:

                        // try to generically "guess" the right exception class name
                        $class = substr($errClass, strlen('javax.jcr.'));
                        $class = explode('.', $class);
                        array_walk($class, function(&$ns) { $ns = ucfirst(str_replace('nodetype', 'NodeType', $ns)); });
                        $class = '\\PHPCR\\'.implode('\\', $class);

                        if (class_exists($class)) {
                            throw new $class($exceptionMsg);
                        } else {
                            throw new \PHPCR\RepositoryException($exceptionMsg . " ($errClass)");
                        }
                }
            }
        }

        if (404 === $httpCode) {
            throw new \PHPCR\PathNotFoundException("HTTP 404 Path Not Found: {$this->method} {$this->uri}");
        } elseif (405 == $httpCode) {
            throw new \Jackalope\Transport\Davex\HTTPErrorException("HTTP 405 Method Not Allowed: {$this->method} {$this->uri}", 405);
        } elseif ($httpCode >= 500) {
            throw new \PHPCR\RepositoryException("HTTP $httpCode Error from backend on: {$this->method} {$this->uri} \n\n$response");
        }

        $curlError = $this->curl->error();


        $msg = "Unexpected error: \nCURL Error: $curlError \nResponse (HTTP $httpCode): {$this->method} {$this->uri} \n\n$response";
        throw new \PHPCR\RepositoryException($msg);
    }

    /**
     * Loads the response into an DOMDocument.
     *
     * Returns a DOMDocument from the backend or throws exception.
     * Does error handling for both connection errors and dcr:exception response
     *
     * @return DOMDocument The loaded XML response text.
     */
    public function executeDom()
    {
        $xml = $this->execute();

        // create new DOMDocument and load the response text.
        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        return $dom;
    }

    /**
     * Loads the server response as a json string.
     *
     * Returns a decoded json string from the backend or throws exception
     *
     * @return mixed
     *
     * @throws \PHPCR\RepositoryException if the json response is not valid
     */
    public function executeJson()
    {
        $response = $this->execute();
        $json = json_decode($response);

        if (null === $json && 'null' !== strtolower($response)) {
            throw new \PHPCR\RepositoryException("Not a valid json object: \nRequest: {$this->method} {$this->uri} \nResponse: \n$response");
        }

        //TODO: are there error responses in json format? if so, handle them
        return $json;
    }
}
