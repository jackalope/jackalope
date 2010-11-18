<?php

namespace jackalope\transport;
use jackalope\Factory, jackalope\TransportInterface;
use \DOMDocument;

/**
 * Connection to one Jackrabbit server.
 * Once the login method has been called, the workspace is set and can not be
 * changed anymore.
 */
class DavexClient implements TransportInterface {
    /** server url including protocol.
     * i.e http://localhost:8080/server/
     * constructor ensures the trailing slash /
     */
    protected $server;
    /** workspace name the transport is bound to */
    protected $workspace;
    /** "$server/$workspace" without trailing slash */
    protected $workspaceUri;
    /**
     * root node path with server domain without trailing slash
     * "$server/$workspace/jcr%3aroot
     * (make sure you never hardcode the jcr%3aroot, its ugly)
     * TODO: apparently, jackrabbit handles the root node by name - it is invisible everywhere for the api, but needed when talking to the backend... could that name change?
     */
    protected $workspaceUriRoot;

    protected $credentials = false;

    /** The cURL resource handle */
    protected $curl = null;

    const USER_AGENT = 'jackalope-php/1.0';
    const NS_DCR = 'http://www.day.com/jcr/webdav/1.0';
    const NS_DAV = 'DAV:';
    const REGISTERED_NAMESPACES = '<?xml version="1.0" encoding="UTF-8"?>< xmlns:dcr="http://www.day.com/jcr/webdav/1.0"/>'; //TODO: unused

    const GET = 'GET';
    const REPORT = 'REPORT';
    const PROPFIND = 'PROPFIND';

    /** Create a transport pointing to a server url.
     *  @param serverUri location of the server
     */
    public function __construct($serverUri) {
        if ('/' !== substr($serverUri, -1, 1)) {
            $serverUri .= '/';
        }
        $this->server = $serverUri;
    }

    public function __destruct() {
        $this->closeConnection();
    }

    /** Opens a cURL session if not yet one open. */
    protected function initConnection() {

        if (!is_null($this->curl)) {
            return false;
        }
        $this->curl = new curl();
        // options in common for all requests
        $this->curl->setopt(CURLOPT_RETURNTRANSFER, 1);
    }

    /** Closes the cURL session. */
    protected function closeConnection() {
        if (is_null($this->curl)) {
            return false;
        }
        $this->curl->close();
        $this->curl = null;
    }

    /**
     * Set this transport to a specific credential and a workspace.
     * This can only be called once. To connect to another workspace or with
     * another credential, use a fresh instance of transport.
     *
     * @param credentials A \PHPCR\SimpleCredentials instance (this is the only type currently understood)
     * @param workspaceName The workspace name for this transport.
     * @return true on success (exceptions on failure)
     * @throws \PHPCR\LoginException if authentication or authorization (for the specified workspace) fails
     * @throws \PHPCR\NoSuchWorkspacexception if the specified workspaceName is not recognized
     * @throws \PHPCR\RepositoryException if another error occurs
     */
    public function login(\PHPCR\CredentialsInterface $credentials, $workspaceName) {
        if ($this->credentials !== false) {
            throw new \PHPCR\RepositoryException('Do not call login twice. Rather instantiate a new Transport object to log in as different user or for a different workspace.');
        }
        if (! $credentials instanceof \PHPCR\SimpleCredentials) {
            throw new \PHPCR\LoginException('Unkown Credentials Type: '.get_class($credentials));
        }

        $this->credentials = $credentials;
        $this->workspace = $workspaceName;
        $this->workspaceUri = $this->server . $workspaceName;
        $this->workspaceUriRoot = $this->workspaceUri . "/jcr%3aroot";
        $dom = $this->getDomFromBackend(self::PROPFIND,
                                        $this->workspaceUri,
                                        self::buildPropfindRequest(array('D:workspace', 'dcr:workspaceName')));

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
        $dom = $this->getDomFromBackend(self::REPORT, $this->server,
                                        self::buildReportRequest('dcr:repositorydescriptors'));
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
     */
    public function getAccessibleWorkspaceNames() {
        $dom = $this->getDomFromBackend(self::PROPFIND,
                                        $this->server,
                                        self::buildPropfindRequest(array('D:workspace')),
                                        1);
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
     * TODO: should we call this getNode? does not work for property. (see ObjectManager::getPropertyByPath for more on properties)
     * @param path absolute path to item
     * @return array for the node (decoded from json)
     * @throws \PHPCR\RepositoryException if now logged in
     */
    public function getItem($path) {
        if ('/' != substr($path, 0, 1)) {
            //sanity check
            throw new \PHPCR\RepositoryException("Implementation error: '$path' is not an absolute path");
        }
        $this->checkLogin();
        return $this->getJsonFromBackend(self::GET, $this->workspaceUriRoot . $path . '.0.json');
    }
    /**
     * Get the node path from a JCR uuid
     * @param uuid the id in JCR format
     * @return string path to the node
     * @throws \PHPCR\ItemNotFoundException if the backend does not know the uuid
     * @throws \PHPCR\RepositoryException if now logged in
     */
    public function getNodePathForIdentifier($uuid) {
        $this->checkLogin();

        $dom = $this->getDomFromBackend(self::REPORT, $this->workspaceUri,
                                        self::buildLocateRequest($uuid));
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
            throw new \PHPCR\RepositoryException("Server answered a path that is not in the current workspace: uuid=$uuid, path=$fullPath, workspace=".$this->workspaceUriRoot);
        }
        return substr(substr($fullPath, 0, -1), //cut trailing slash /
                      strlen($this->workspaceUriRoot)); //remove uri, workspace and root node
    }

    /**
     * get the registered namespaces mappings from the backend
     * @return associative array of prefix => uri
     * @throws \PHPCR\RepositoryException if now logged in
     */
    public function getNamespaces() {
        $this->checkLogin();

        $dom = $this->getDomFromBackend(self::REPORT, $this->workspaceUri,
                                        self::buildReportRequest('dcr:registerednamespaces'));
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
     * Throws an error if the there is no login
     * @throws \PHPCR\RepositoryException if now logged in
     */
    protected function checkLogin() {
        if (empty($this->workspaceUri)) {
            throw new \PHPCR\RepositoryException("Implementation error: Please login before accessing content");
        }
    }

    /**
     * Returns the XML required to request nodetypes
     * @param array the nodetypes you want to request
     * @return string XML with the request
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
        $xml = '<?xml version="1.0" encoding="UTF-8"?><D:propfind xmlns:D="DAV:" xmlns:dcr="http://www.day.com/jcr/webdav/1.0"><D:prop>';
        if (!is_array($properties)) {
            $properties = array($properties);
        }
        foreach($properties as $property) {
            $xml .= '<'. $property . '/>';
        }
        $xml .= '</D:prop></D:propfind>';
        return $xml;
    }

    /** build a REPORT XML request string */
    protected static function buildReportRequest($name) {
        return '<?xml version="1.0" encoding="UTF-8"?><' .
                $name .
               ' xmlns:dcr="http://www.day.com/jcr/webdav/1.0"/>';
    }
    /** build REPORT XML request for locating a node path by uuid */
    protected static function buildLocateRequest($uuid) {
        return '<?xml version="1.0" encoding="UTF-8"?><dcr:locate-by-uuid xmlns:dcr="http://www.day.com/jcr/webdav/1.0"><D:href xmlns:D="DAV:">' .
                $uuid .
               '</D:href></dcr:locate-by-uuid>';
    }

    /**
     * Set the standard parameters for a curl session.
     * If you only use this function, you can do a multi request session
     * without fearing that information from one request messes with the
     * next request.
     *
     * @param string type the http method to use
     * @param string uri the uri to request
     * @param string body the body to send as post, default is empty
     * @param int depth How far the request should go, default is 0 (setting the Depth HTTP header)
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
     * Takes a curl handle prepared by prepareRequest, executes it and checks
     * for transport level errors, throwing the appropriate exceptions.
     * @return raw data string
     * @throws \PHPCR\RepositoryExceptions and descendants on connection errors
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
     * Returns a DOMDocument from the backend or throws exception
     * Does error handling for both connection errors and dcr:exception response
     *
     * @param curl The curl handle you want to fetch from
     * @return DOMDocument the loaded XML
     * @throws \PHPCR\RepositoryException
     * @throws \PHPCR\NoSuchWorkspaceException
     */
    protected function getDomFromBackend($type, $uri, $body='', $depth=0) {
        $this->prepareRequest($type, $uri, $body, $depth);
        $xml = $this->getRawFromBackend();
        $dom = new DOMDocument();
        $dom->loadXML($xml);

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
     * Returns a DOMDocument from the backend or throws exception
     * Does error handling for both connection errors and json problems
     *
     * @param curl The curl handle you want to fetch from
     * @return array decoded json
     * @throws \PHPCR\ItemNotFoundException if the response is not valid
     * @throws \PHPCR\RepositoryException
     */
    protected function getJsonFromBackend($type, $uri, $body='', $depth=0) {
        //OPTIMIZE: re-use connection. JACK-7
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
