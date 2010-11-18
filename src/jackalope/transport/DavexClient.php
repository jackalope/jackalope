<?php
/**
 * Class to handle the communication between Jackalope and Jackrabbit via Davex.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 *
 * @package jackalope
 * @subpackage transport
 */

namespace jackalope\transport;
use jackalope\Factory, jackalope\TransportInterface;
use \DOMDocument;

/**
 * Connection to one Jackrabbit server.
 *
 * Once the login method has been called, the workspace is set and can not be changed anymore.
 *
 * @package jackalope
 * @subpackage transport
 */
class DavexClient implements TransportInterface {

    /**
     * Name of the user agent to be exposed to a client.
     * @var string
     */
    const USER_AGENT = 'jackalope-php/1.0';

    /**
     * Description of the namspace to be used for communication with the server.
     * @var string
     */
    const NS_DCR = 'http://www.day.com/jcr/webdav/1.0';

    /**
     * Identifier of the used namespace.
     * @var string
     */
    const NS_DAV = 'DAV:';

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
     * Representation of a XML string header.
     *
     * @todo TODO: seems not to be used anymore.
     *
     * @var string
     */
    const REGISTERED_NAMESPACES =
        '<?xml version="1.0" encoding="UTF-8"?>< xmlns:dcr="http://www.day.com/jcr/webdav/1.0"/>';

    /**
     * Server url including protocol.
     *
     * i.e http://localhost:8080/server/
     * constructor ensures the trailing slash /
     *
     * @var string
     */
    protected $server;

    /**
     * Workspace name the transport is bound to
     * @var string
     */
    protected $workspace;

    /**
     * Identifier of the workspace including the used protocol and server name.
     *
     * "$server/$workspace" without trailing slash
     *
     *  @var string
     */
    protected $workspaceUri;

    /**
     * Root node path with server domain without trailing slash.
     *
     * "$server/$workspace/jcr%3aroot
     * (make sure you never hardcode the jcr%3aroot, its ugly)
     * @todo TODO: apparently, jackrabbit handles the root node by name - it is invisible everywhere for the api,
     *             but needed when talking to the backend... could that name change?
     *
     * @var string
     */
    protected $workspaceUriRoot;

    /**
     * Set of credentials necessary to connect to the server or else.
     * @var \PHPCR\CredentialsInterface
     */
    protected $credentials = false;

    /**
     * The cURL resource handle
     * @var curl
     */
    protected $curl = null;

    /**
     * Create a transport pointing to a server url.
     *
     * @param serverUri location of the server
     */
    public function __construct($serverUri) {
        // append a slash if not there
        if ('/' !== substr($serverUri, -1, 1)) {
            $serverUri .= '/';
        }
        $this->server = $serverUri;
    }

    /**
     * Tidies up the current cUrl connection.
     */
    public function __destruct() {
        $this->closeConnection();
    }

    /**
     * Opens a cURL session if not yet one open.
     *
     * @return null|false False in case there is already an open connection, else null;
     */
    protected function initConnection() {

        if (!is_null($this->curl)) {
            return false;
        }
        $this->curl = new curl();
        // options in common for all requests
        $this->curl->setopt(CURLOPT_RETURNTRANSFER, 1);
    }

    /**
     * Closes the cURL session.
     *
     * @return null|false False in case there is no open connection, else null;
     */
    protected function closeConnection() {
        if (is_null($this->curl)) {
            return false;
        }
        $this->curl->close();
        $this->curl = null;
    }

    /**
     * Set this transport to a specific credential and a workspace.
     *
     * This can only be called once. To connect to another workspace or with
     * another credential, use a fresh instance of transport.
     *
     * @param \PHPCR\CredentialsInterface $credentials A set of attributes to be used to login per example.
     * @param string $workspaceName The workspace name for this transport.
     * @return boolean True on success, exceptions on failure.
     *
     * @throws \PHPCR\LoginException if authentication or authorization (for the specified workspace) fails
     * @throws \PHPCR\NoSuchWorkspacexception if the specified workspaceName is not recognized
     * @throws \PHPCR\RepositoryException if another error occurs
     */
    public function login(\PHPCR\CredentialsInterface $credentials, $workspaceName) {
        if ($this->credentials !== false) {
            throw new \PHPCR\RepositoryException(
                'Do not call login twice. Rather instantiate a new Transport object '.
                'to log in as different user or for a different workspace.'
            );
        }
        if (! $credentials instanceof \PHPCR\SimpleCredentials) {
            throw new \PHPCR\LoginException('Unkown Credentials Type: '.get_class($credentials));
        }

        $this->credentials = $credentials;
        $this->workspace = $workspaceName;
        $this->workspaceUri = $this->server . $workspaceName;
        $this->workspaceUriRoot = $this->workspaceUri . "/jcr%3aroot";
        $dom = $this->getDomFromBackend(
            self::PROPFIND,
            $this->workspaceUri,
            self::buildPropfindRequest(array('D:workspace', 'dcr:workspaceName'))
        );

        $set = $dom->getElementsByTagNameNS(self::NS_DCR, 'workspaceName');
        if ($set->length != 1) {
            throw new \PHPCR\RepositoryException('Unexpected answer from server: '.$dom->saveXML());
        }

        if ($set->item(0)->textContent != $this->workspace) {
            throw new \PHPCR\RepositoryException('Wrong workspace in answer from server: '.$dom->saveXML());
        }
        return true;
    }

    /**
     * Get the repository descriptors from the jackrabbit server
     * This happens without login or accessing a specific workspace.
     *
     * @return Array with name => Value for the descriptors
     * @throws \PHPCR\RepositoryException if error occurs
     */
     public function getRepositoryDescriptors() {

         $dom = $this->getDomFromBackend(
            self::REPORT, $this->server,
            self::buildReportRequest('dcr:repositorydescriptors')
        );

        if ($dom->firstChild->localName != 'repositorydescriptors-report' ||
            $dom->firstChild->namespaceURI != self::NS_DCR) {
            throw new \PHPCR\RepositoryException('Error talking to the backend. '.$dom->saveXML());
        }

        $descs = $dom->getElementsByTagNameNS(self::NS_DCR, 'descriptor');
        $descriptors = array();
        foreach($descs as $desc) {
            $values = array();
            foreach($desc->getElementsByTagNameNS(self::NS_DCR, 'descriptorvalue') as $value) {
                $values[] = $value->textContent;
            }
            if ($desc->childNodes->length == 2) {
                //there was one type and one value => this is a single value property
                //TODO: is this the correct assumption? or should the backend tell us specifically?
                $descriptors[$desc->firstChild->textContent] = $values[0];
            } else {
                $descriptors[$desc->firstChild->textContent] = $values;
            }
        }
        return $descriptors;
    }

    /**
     * Returns the accessible workspace names
     *
     * @return array Set of workspaces to work on.
     */
    public function getAccessibleWorkspaceNames() {
        $dom = $this->getDomFromBackend(
            self::PROPFIND,
            $this->server,
            self::buildPropfindRequest(array('D:workspace')),
            1
        );
        $workspaces = array();
        foreach ($dom->getElementsByTagNameNS(self::NS_DAV, 'workspace') as $value) {
            if (! empty($value->nodeValue)) {
                $workspaces[] = substr(trim($value->nodeValue), strlen($this->server), -1);
            }
        }
        return array_unique($workspaces);
    }

    /**
     * Get the item from an absolute path
     *
     * TODO: should we call this getNode? does not work for property. (see ObjectManager::getPropertyByPath for more on properties)
     *
     * @param string $path Absolute path to identify a special item.
     * @return array for the node (decoded from json)
     *
     * @throws \PHPCR\RepositoryException if now logged in
     */
    public function getItem($path) {

        // it has to be an absolute path!
        if ('/' != substr($path, 0, 1)) {
            //sanity check
            throw new \PHPCR\RepositoryException("Implementation error: '$path' is not an absolute path");
        }
        $this->checkLogin();
        return $this->getJsonFromBackend(self::GET, $this->workspaceUriRoot . $path . '.0.json');
    }
    /**
     * Get the node path from a JCR uuid
     *
     * @param string $uuid the id in JCR format
     * @return string Absolute path to the node
     *
     * @throws \PHPCR\ItemNotFoundException if the backend does not know the uuid
     * @throws \PHPCR\RepositoryException if now logged in
     */
    public function getNodePathForIdentifier($uuid) {
        $this->checkLogin();

        $dom = $this->getDomFromBackend(
            self::REPORT, $this->workspaceUri,
            self::buildLocateRequest($uuid)
        );
        /* answer looks like
           <D:multistatus xmlns:D="DAV:">
             <D:response>
                 <D:href>http://localhost:8080/server/tests/jcr%3aroot/tests_level1_access_base/idExample/</D:href>
             </D:response>
         </D:multistatus>
        */
        $set = $dom->getElementsByTagNameNS(self::NS_DAV, 'href');
        if ($set->length != 1) {
            throw new \PHPCR\RepositoryException('Unexpected answer from server: '.$dom->saveXML());
        }
        $fullPath = $set->item(0)->textContent;
        if (strncmp($this->workspaceUriRoot, $fullPath, strlen($this->workspaceUri))) {
            throw new \PHPCR\RepositoryException(
                "Server answered a path that is not in the current workspace: uuid=$uuid, path=$fullPath, workspace=".
                $this->workspaceUriRoot
            );
        }
        return substr(
            substr($fullPath, 0, -1), //cut trailing slash /
            strlen($this->workspaceUriRoot) //remove uri, workspace and root node
        );
    }

    /**
     * Get the registered namespaces mappings from the backend.
     *
     * @return array Associative array of prefix => uri
     *
     * @throws \PHPCR\RepositoryException if now logged in
     */
    public function getNamespaces() {
        $this->checkLogin();

        $dom = $this->getDomFromBackend(
            self::REPORT, $this->workspaceUri,
            self::buildReportRequest('dcr:registerednamespaces')
        );

        if ($dom->firstChild->localName != 'registerednamespaces-report' ||
            $dom->firstChild->namespaceURI != self::NS_DCR) {
            throw new \PHPCR\RepositoryException('Error talking to the backend. '.$dom->saveXML());
        }

        $mappings = array();
        $namespaces = $dom->getElementsByTagNameNS(self::NS_DCR, 'namespace');
        foreach($namespaces as $elem) {
            $mappings[$elem->firstChild->textContent] = $elem->lastChild->textContent;
        }
        return $mappings;
    }

    /**
     * Returns node types
     * @param array nodetypes to request
     * @return dom with the definitions
     * @throws \PHPCR\RepositoryException if now logged in
     */
    public function getNodeTypes($nodeTypes = array()) {
        $this->checkLogin();

        $dom = $this->getDomFromBackend(
            self::REPORT, $this->workspaceUri . '/jcr:root',
            self::buildNodeTypesRequest($nodeTypes)
        );
        if ($dom->firstChild->localName != 'nodeTypes') {
            throw new \PHPCR\RepositoryException('Error talking to the backend. '.$dom->saveXML());
        }
        return $dom;
    }

    /**
     * Chechs if login procedure has been passed already.
     *
     * @throws \PHPCR\RepositoryException if not logged in
     */
    protected function checkLogin() {
        if (empty($this->workspaceUri)) {
            throw new \PHPCR\RepositoryException("Implementation error: Please login before accessing content");
        }
    }

    /**
     * Returns the XML required to request nodetypes
     *
     * @param array $nodesType The list of nodetypes you want to request for.
     * @return string XML with the request information.
     */
    protected static function buildNodeTypesRequest(Array $nodeTypes) {
        $xmlStr = '<?xml version="1.0" encoding="utf-8" ?><jcr:nodetypes xmlns:jcr="http://www.day.com/jcr/webdav/1.0">';
        if (empty($nodeTypes)) {
            $xmlStr .= '<jcr:all-nodetypes/>';
        } else {
            foreach ($nodeTypes as $nodetype) {
                $xmlStr .= '<jcr:nodetype><jcr:nodetypename>'.$nodetype.'</jcr:nodetypename></jcr:nodetype>';
            }
        }
        $xmlStr .='</jcr:nodetypes>';

        return $xmlStr;
    }

    /**
     * Build PROPFIND request XML for the specified property names
     *
     * @param array $properties names of the properties to search for
     * @return string XML to post in the body
     */
    protected static function buildPropfindRequest($properties) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.
            '<D:propfind xmlns:D="DAV:" xmlns:dcr="http://www.day.com/jcr/webdav/1.0"><D:prop>';
        if (!is_array($properties)) {
            $properties = array($properties);
        }
        foreach($properties as $property) {
            $xml .= '<'. $property . '/>';
        }
        $xml .= '</D:prop></D:propfind>';
        return $xml;
    }

    /**
     * Build a REPORT XML request string
     *
     * @param string $name Name of the resource to be requested.
     * @return string XML string representing the head of the request.
     */
    protected static function buildReportRequest($name) {
        return '<?xml version="1.0" encoding="UTF-8"?><' .
                $name .
               ' xmlns:dcr="http://www.day.com/jcr/webdav/1.0"/>';
    }

    /**
     * Build REPORT XML request for locating a node path by uuid
     *
     * @param string $uuid Unique identifier of the node to be asked for.
     * @return string XML sring representing the content of the request.
     */
    protected static function buildLocateRequest($uuid) {
        return '<?xml version="1.0" encoding="UTF-8"?>'.
               '<dcr:locate-by-uuid xmlns:dcr="http://www.day.com/jcr/webdav/1.0">'.
               '<D:href xmlns:D="DAV:">' .
                $uuid .
               '</D:href></dcr:locate-by-uuid>';
    }

    /**
     * Set the standard parameters for a curl session.
     *
     * If you only use this function, you can do a multi request session
     * without fearing that information from one request messes with the
     * next request.
     *
     * @param string $type The http method to use to communicate with the server.
     * @param string $uri The uri of the node to request from the server.
     * @param string $body The body to send as post, default is empty.
     * @param integer $depth How far the request should go, default is 0 (setting the Depth HTTP header)
     *
     * @uses curl::setopt()
     */
    protected function prepareRequest($type, $uri, $body = '', $depth = 0) {

        // make sure we have a curl handle
        $this->initConnection();

        if ($this->credentials instanceof \PHPCR\SimpleCredentials) {
            $this->curl->setopt(CURLOPT_USERPWD, $this->credentials->getUserID().':'.$this->credentials->getPassword());
        }

        $headers = array(
            'Depth: ' . $depth,
            'Content-Type: text/xml; charset=UTF-8',
            'User-Agent: '.self::USER_AGENT
        );

        $this->curl->setopt(CURLOPT_CUSTOMREQUEST, $type);
        $this->curl->setopt(CURLOPT_URL, $uri);
        $this->curl->setopt(CURLOPT_HTTPHEADER, $headers);
        $this->curl->setopt(CURLOPT_POSTFIELDS, $body);
    }

    /**
     * Requests the data to be identified by a formerly prepared request.
     *
     * Takes a curl handle prepared by prepareRequest, executes it and checks
     * for transport level errors, throwing the appropriate exceptions.
     *
     * @return string XML representation of the response.
     *
     * @throws \PHPCR\NoSuchWorkspaceException if it was not possible to reach the server (resolve host or connect)
     * @throws \PHPCR\RepositoryExceptions if on any other error.
     *
     * @uses curl::errno()
     * @uses curl::exec()
     */
    protected function getRawFromBackend() {

        $data = $this->curl->exec();

        if (NULL === $data || empty($data)) {
            switch($this->curl->errno()) {
                case CURLE_COULDNT_RESOLVE_HOST:
                case CURLE_COULDNT_CONNECT:
                    throw new \PHPCR\NoSuchWorkspaceException($this->curl->error());
                default:
                    $curlError = $this->curl->error();
                    $msg = 'No data returned by server: ';
                    $msg .= empty($curlError) ? 'No reason given by curl.' : $curlError;
                    throw new \PHPCR\RepositoryException($msg);
            }
        }

        return $data;
    }

    /**
     * Loads the response into an DOMDocument.
     *
     * Returns a DOMDocument from the backend or throws exception.
     * Does error handling for both connection errors and dcr:exception response
     *
     * @param string $type Identifier of the request type (e.g. DELETE, GET, POST, CONNECT, …)
     * @param string $uri String identifying the resource to be called.
     * @param string $body the body to send as post, default is empty
     * @param integer $depth How far the request should go, default is 0 (setting the Depth HTTP header)
     * @return DOMDocument The loaded XML response text.
     *
     * @throws \PHPCR\NoSuchWorkspaceException
     * @throws \PHPCR\NodeType\NoSuchNodeTypeException
     * @throws \PHPCR\ItemNotFoundException
     * @throws \PHPCR\RepositoryException
     *
     * @uses curl::getInfo()
     */
    protected function getDomFromBackend($type, $uri, $body='', $depth=0) {

        // request information from the server via HTTP
        $this->prepareRequest($type, $uri, $body, $depth);
        $xml = $this->getRawFromBackend();

        // create new DOMDocument and load the response text.
        $dom = new DOMDocument();
        $dom->loadXML($xml);

        // determine if there is an error returned
        $err = $dom->getElementsByTagNameNS(self::NS_DCR, 'exception');
        if ($err->length > 0) {

            //TODO: can we trust jackrabbit to always have an exception node if status is not OK?
            $status = $this->curl->getinfo(CURLINFO_HTTP_CODE);

            $err = $err->item(0);
            $errClass = $err->getElementsByTagNameNS(self::NS_DCR, 'class')->item(0)->textContent;
            $errMsg = $err->getElementsByTagNameNS(self::NS_DCR, 'message')->item(0)->textContent;

            switch($errClass) {
                case 'javax.jcr.NoSuchWorkspaceException':
                    throw new \PHPCR\NoSuchWorkspaceException('HTTP '.$status . ": $errMsg");
                case 'javax.jcr.nodetype.NoSuchNodeTypeException':
                    throw new \PHPCR\NodeType\NoSuchNodeTypeException('HTTP '.$status . ": $errMsg");
                case 'javax.jcr.ItemNotFoundException':
                    throw new \PHPCR\ItemNotFoundException('HTTP '.$status . ": $errMsg");

                //TODO: map more errors here?
                default:
                    throw new \PHPCR\RepositoryException('HTTP '.$status . ": $errMsg ($errClass)");
            }
        }
        return $dom;
    }

    /**
     * Loads the server response into a DOMDocument.
     *
     * Returns a DOMDocument from the backend or throws exception
     * Does error handling for both connection errors and json problems
     *
     * @param string $type Identifier of the request type (e.g. DELETE, GET, POST, CONNECT, …)
     * @param string $uri String identifying the resource to be called.
     * @param string $body the body to send as post, default is empty
     * @param integer $depth How far the request should go, default is 0 (setting the Depth HTTP header)
     * @return object DOMDocument or DOMDOcumentFragment representing the response.
     *
     * @throws \PHPCR\ItemNotFoundException if the response is not valid
     * @throws \PHPCR\RepositoryException
     *
     * @uses curl::getInfo()
     */
    protected function getJsonFromBackend($type, $uri, $body='', $depth=0) {

        //@todo: OPTIMIZE: re-use connection. JACK-7
        //@todo: prepareRequest only returns XML never json. It has a fixed content-type.
        $this->prepareRequest($type, $uri, $body, $depth);
        $jsonstring = $this->getRawFromBackend();

        $json = json_decode($jsonstring);
        if (! is_object($json)) {
            $status = $this->curl->getinfo(CURLINFO_HTTP_CODE);
            if (404 === $status) {
                throw new \PHPCR\ItemNotFoundException('Path not found: ' . $uri);
            } elseif (500 <= $status) {
                throw new \PHPCR\RepositoryException("Error from backend for '$type' '$uri'\n$jsonstring");
            } else {
                //FIXME: this might be an xml error response like
                /*
                <?xml version="1.0" encoding="UTF-8"?>
                  <D:error xmlns:D="DAV:">
                      <dcr:exception xmlns:dcr="http://www.day.com/jcr/webdav/1.0">
                          <dcr:class>javax.jcr.NamespaceException</dcr:class>
                          <dcr:message>jackalope-api-tests: is not a registered namespace prefix.</dcr:message>
                      </dcr:exception>
                  </D:error>
                */

                throw new \PHPCR\RepositoryException("Not a valid json object. '$status' '$jsonstring' ('$type'  '$uri')");
            }
        }
        //TODO: are there error responses in json format? if so, handle them
        return $json;
    }
}
