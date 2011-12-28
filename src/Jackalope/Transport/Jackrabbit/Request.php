<?php

namespace Jackalope\Transport\Jackrabbit;

use DOMDocument;

use PHPCR\SimpleCredentials;
use PHPCR\RepositoryException;
use PHPCR\NoSuchWorkspaceException;
use PHPCR\ItemNotFoundException;
use PHPCR\PathNotFoundException;
use PHPCR\ReferentialIntegrityException;
use PHPCR\NodeType\ConstraintViolationException;
use PHPCR\NodeType\NoSuchNodeTypeException;

use Jackalope\Transport\curl;
use Jackalope\FactoryInterface;

/**
 * Request class for the Davex protocol
 *
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache License Version 2.0, January 2004
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
     * Identifier of the 'POST' http request method.
     * @var string
     */
    const POST = 'POST';
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
     * Identifier of the 'LOCK' http request method
     * @var string
     */
    const LOCK = 'LOCK';


    /**
     * Identifier of the 'UNLOCK' http request method
     * @var string
     */
    const UNLOCK = 'UNLOCK';

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
     * @var curl
     */
    protected $curl;

    /**
     * Name of the request method to be used.
     * @var string
     */
    protected $method;

    /**
     * Url(s) to get/post/..
     * @var array
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
     * The lock token active for this request otherwise FALSE for no locking
     * @var string|FALSE
     */
    protected $lockToken = false;

    /**
     * The transaction id active for this request otherwise FALSE for not
     * performing a transaction
     * @var string|FALSE
     */
    protected $transactionId = false;

    /**
     * Initiaties the NodeTypes request object.
     *
     * @param object $factory Ignored for now, as this class does not create objects
     * TODO: document other parameters
     */
    public function __construct(FactoryInterface $factory, $curl, $method, $uri)
    {
        $this->curl = $curl;
        $this->method = $method;
        $this->setUri($uri);
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
     * @param int|string  $depth
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
        if (!is_array($uri)) {
            $this->uri = array($uri => $uri);
        } else {
            $this->uri = $uri;
        }
    }

    public function addHeader($header)
    {
        $this->additionalHeaders[] = $header;
    }

    public function setLockToken($lockToken)
    {
        $this->lockToken = (string) $lockToken;
    }

    public function setTransactionId($transactionId)
    {
        $this->transactionId = (string) $transactionId;
    }

    /**
     * used by multiCurl with fresh curl instances
     */
    protected function prepareCurl($curl, $getCurlObject)
    {
        if ($this->credentials instanceof SimpleCredentials) {
            $curl->setopt(CURLOPT_USERPWD, $this->credentials->getUserID().':'.$this->credentials->getPassword());
        }
        // otherwise leave this alone, the new curl instance has no USERPWD yet

        $headers = array(
            'Depth: ' . $this->depth,
            'Content-Type: '.$this->contentType,
            'User-Agent: '.self::USER_AGENT
        );
        $headers = array_merge($headers, $this->additionalHeaders);

        if ($this->lockToken) {
            $headers[] = 'Lock-Token: <'.$this->lockToken.'>';
        }

        if ($this->transactionId) {
            $headers[] = 'TransactionId: <'.$this->transactionId.'>';
        }

        $curl->setopt(CURLOPT_RETURNTRANSFER, true);
        $curl->setopt(CURLOPT_CUSTOMREQUEST, $this->method);

        $curl->setopt(CURLOPT_HTTPHEADER, $headers);
        $curl->setopt(CURLOPT_POSTFIELDS, $this->body);
        if ($getCurlObject) {
            $curl->parseResponseHeaders();
        }
        return $curl;
    }

    /**
     * Requests the data to be identified by a formerly prepared request.
     *
     * Prepares the curl object, executes it and checks
     * for transport level errors, throwing the appropriate exceptions.
     *
     * @param bool $getCurlObject if to return the curl object instead of the response
     *
     * @return string|array of XML representation of the response.
     */
    public function execute($getCurlObject = false, $forceMultiple = false)
    {
        if (!$forceMultiple && count($this->uri) === 1) {
            return $this->singleRequest($getCurlObject);
        }
        return $this->multiRequest($getCurlObject);
    }

    /**
     * Requests the data for multiple requests
     *
     * @param bool $getCurlObject if to return the curl object instead of the response
     *
     * @return array of XML representations of responses or curl objects.
     */
    protected function multiRequest($getCurlObject = false)
    {
        $mh = curl_multi_init();

        $curls = array();
        foreach ($this->uri as $absPath => $uri) {
            $tempCurl = new curl($uri);
            $tempCurl = $this->prepareCurl($tempCurl, $getCurlObject);
            $curls[$absPath] = $tempCurl;
            curl_multi_add_handle($mh, $tempCurl->getCurl());
        }

        $active = null;

        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($active || $mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && CURLM_OK == $mrc) {
            if (-1 != curl_multi_select($mh)) {
                do {
                    $mrc = curl_multi_exec($mh, $active);
                } while (CURLM_CALL_MULTI_PERFORM == $mrc);
            }
        }

        $responses = array();
        foreach ($curls as $key => $curl) {
            if (empty($failed)) {
                $httpCode = $curl->getinfo(CURLINFO_HTTP_CODE);
                if ($httpCode >= 200 && $httpCode < 300) {
                    if ($getCurlObject) {
                        $responses[$key] = $curl;
                    } else {
                        $responses[$key] = curl_multi_getcontent($curl->getCurl());
                    }
                }
            }
            curl_multi_remove_handle($mh, $curl->getCurl());
        }
        curl_multi_close($mh);
        return $responses;
    }

    /**
     * Requests the data for a single requests
     *
     * @param bool $getCurlObject if to return the curl object instead of the response
     *
     * @return string XML representation of a response or curl object.
     */
    protected function singleRequest($getCurlObject)
    {
        if ($this->credentials instanceof SimpleCredentials) {
            $this->curl->setopt(CURLOPT_USERPWD, $this->credentials->getUserID().':'.$this->credentials->getPassword());
            $curl = $this->curl;
        } else {
            // we seem to be unable to remove the Authorization header
            // setting to null produces a bogus Authorization: Basic Og==
            $curl = new curl;
        }

        $headers = array(
            'Depth: ' . $this->depth,
            'Content-Type: '.$this->contentType,
            'User-Agent: '.self::USER_AGENT
        );
        $headers = array_merge($headers, $this->additionalHeaders);

        if ($this->lockToken) {
            $headers[] = 'Lock-Token: <'.$this->lockToken.'>';
        }

        if ($this->transactionId) {
            $headers[] = 'TransactionId: <'.$this->transactionId.'>';
        }

        $curl->setopt(CURLOPT_RETURNTRANSFER, true);
        $curl->setopt(CURLOPT_CUSTOMREQUEST, $this->method);
        $curl->setopt(CURLOPT_URL, reset($this->uri));
        $curl->setopt(CURLOPT_HTTPHEADER, $headers);
        $curl->setopt(CURLOPT_POSTFIELDS, $this->body);
        if ($getCurlObject) {
            $curl->parseResponseHeaders();
        }

        $response = $curl->exec();
        $curl->setResponse($response);

        $httpCode = $curl->getinfo(CURLINFO_HTTP_CODE);
        if ($httpCode >= 200 && $httpCode < 300) {
            if ($getCurlObject) {
                return $curl;
            }
            return $response;
        }
        $this->handleError($curl, $response, $httpCode);
    }

    /**
     * Handles errors caused by singleRequest and multiRequest
     *
     * for transport level errors, throwing the appropriate exceptions.
     * @throws NoSuchWorkspaceException if it was not possible to reach the server (resolve host or connect)
     * @throws ItemNotFoundException if the object was not found
     * @throws RepositoryExceptions if on any other error.
     * @throws PathNotFoundException if the path was not found (server returned 404 without xml response)
     *
     */
    protected function handleError($curl, $response, $httpCode)
    {
        switch ($curl->errno()) {
            case CURLE_COULDNT_RESOLVE_HOST:
            case CURLE_COULDNT_CONNECT:
                throw new NoSuchWorkspaceException($curl->error());
        }

        // TODO extract HTTP status string from response, more descriptive about error

        // use XML error response if it's there
        if (substr($response, 0, 1) === '<') {
            $dom = new DOMDocument();
            $dom->loadXML($response);
            $err = $dom->getElementsByTagNameNS(Client::NS_DCR, 'exception');
            if ($err->length > 0) {
                $err = $err->item(0);
                $errClass = $err->getElementsByTagNameNS(Client::NS_DCR, 'class')->item(0)->textContent;
                $errMsg = $err->getElementsByTagNameNS(Client::NS_DCR, 'message')->item(0)->textContent;

                $exceptionMsg = 'HTTP ' . $httpCode . ': ' . $errMsg;
                switch($errClass) {
                    case 'javax.jcr.NoSuchWorkspaceException':
                        throw new NoSuchWorkspaceException($exceptionMsg);
                    case 'javax.jcr.nodetype.NoSuchNodeTypeException':
                        throw new NoSuchNodeTypeException($exceptionMsg);
                    case 'javax.jcr.ItemNotFoundException':
                        throw new ItemNotFoundException($exceptionMsg);
                    case 'javax.jcr.nodetype.ConstraintViolationException':
                        throw new ConstraintViolationException($exceptionMsg);
                    case 'javax.jcr.ReferentialIntegrityException':
                        throw new ReferentialIntegrityException($exceptionMsg);
                    //TODO: Two more errors needed for Transactions. How does the corresponding Jackrabbit response look like?
                    // javax.transaction.RollbackException => \PHPCR\Transaction\RollbackException
                    // java.lang.SecurityException => \PHPCR\AccessDeniedException

                    //TODO: map more errors here?
                    default:

                        // try to generically "guess" the right exception class name
                        $class = substr($errClass, strlen('javax.jcr.'));
                        $class = explode('.', $class);
                        array_walk($class, function(&$ns) { $ns = ucfirst(str_replace('nodetype', 'NodeType', $ns)); });
                        $class = '\\PHPCR\\'.implode('\\', $class);

                        if (class_exists($class)) {
                            throw new $class($exceptionMsg);
                        }
                        throw new RepositoryException($exceptionMsg . " ($errClass)");
                }
            }
        }
        if (404 === $httpCode) {
            throw new PathNotFoundException("HTTP 404 Path Not Found: {$this->method} ".var_export($this->uri, true));
        } elseif (405 == $httpCode) {
            throw new HTTPErrorException("HTTP 405 Method Not Allowed: {$this->method} ".var_export($this->uri, true), 405);
        } elseif ($httpCode >= 500) {
            throw new RepositoryException("HTTP $httpCode Error from backend on: {$this->method} ".var_export($this->uri, true)."\n\n$response");
        }

        $curlError = $curl->error();

        $msg = "Unexpected error: \nCURL Error: $curlError \nResponse (HTTP $httpCode): {$this->method} ".var_export($this->uri, true)."\n\n$response";
        throw new RepositoryException($msg);
    }

    /**
     * Loads the response into an DOMDocument.
     *
     * Returns a DOMDocument from the backend or throws exception.
     * Does error handling for both connection errors and dcr:exception response
     *
     * @return DOMDocument The loaded XML response text.
     */
    public function executeDom($forceMultiple = false)
    {
        $xml = $this->execute(null, $forceMultiple);

        // create new DOMDocument and load the response text.
        $dom = new DOMDocument();
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
     * @throws RepositoryException if the json response is not valid
     */
    public function executeJson($forceMultiple = false)
    {
        $responses = $this->execute(null, $forceMultiple);
        if (!is_array($responses)) {
            $responses = array($responses);
            $reset = true;
        }

        $json = array();
        foreach ($responses as $key => $response) {
            $json[$key] = json_decode($response);
            if (null === $json[$key] && 'null' !== strtolower($response)) {
                $uri = reset($this->uri); // FIXME was $this->uri[$key]. at which point did we lose the right key?
                throw new RepositoryException("Not a valid json object: \nRequest: {$this->method} $uri \nResponse: \n$response");
            }
        }
        //TODO: are there error responses in json format? if so, handle them
        if (isset($reset)) {
            return reset($json);
        }
        return $json;
    }
}
