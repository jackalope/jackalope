<?php

/**
 * Connection to one Jackrabbit server.
 * Once the login method has been called, the workspace is set and can not be
 * changed anymore.
 */
class jackalope_transport_DavexClient implements jackalope_TransportInterface {
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
        $this->curl = new jackalope_transport_curl();
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
     * @param credentials A PHPCR_SimpleCredentials instance (this is the only type currently understood)
     * @param workspaceName The workspace name for this transport.
     * @return true on success (exceptions on failure)
     * @throws PHPCR_LoginException if authentication or authorization (for the specified workspace) fails
     * @throws PHPCR_NoSuchWorkspacexception if the specified workspaceName is not recognized
     * @throws PHPCR_RepositoryException if another error occurs
     */
    public function login(PHPCR_CredentialsInterface $credentials, $workspaceName) {
        if ($this->credentials !== false) {
            throw new PHPCR_RepositoryException('Do not call login twice. Rather instantiate a new Transport object to log in as different user or for a different workspace.');
        }
        if (! $credentials instanceof PHPCR_SimpleCredentials) {
            throw new PHPCR_LoginException('Unkown Credentials Type: '.get_class($credentials));
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
            throw new PHPCR_RepositoryException('Unexpected answer from server: '.$dom->saveXML());
        }
        if ($set->item(0)->textContent != $this->workspace) {
            throw new PHPCR_RepositoryException('Wrong workspace in answer from server: '.$dom->saveXML());
        }
        return true;
    }

    /**
     * Get the repository descriptors from the jackrabbit server
     * This happens without login or accessing a specific workspace.
     *
     * @return Array with name => Value for the descriptors
     * @throws PHPCR_RepositoryException if error occurs
     */
     public function getRepositoryDescriptors() {
        $dom = $this->getDomFromBackend(self::REPORT, $this->server,
                                        self::buildReportRequest('dcr:repositorydescriptors'));
        if ($dom->firstChild->localName != 'repositorydescriptors-report' ||
            $dom->firstChild->namespaceURI != self::NS_DCR) {
            throw new PHPCR_RepositoryException('Error talking to the backend. '.$dom->saveXML());
        }

        $descs = $dom->getElementsByTagNameNS(self::NS_DCR, 'descriptor');
        $descriptors = array();
        foreach($descs as $desc) {
            $values = array();
            foreach($desc->getElementsByTagNameNS(self::NS_DCR, 'descriptorvalue') as $value) {
                $type = $value->getAttribute('type');
                if ($type == '') $type = PHPCR_PropertyType::TYPENAME_UNDEFINED;
                $values[] = jackalope_Factory::get('Value', array($type, $value->textContent));
            }
            if ($desc->childNodes->length == 2) {
                $descriptors[$desc->firstChild->textContent] = $values[0];
            } else {
                $descriptors[$desc->firstChild->textContent] = $values;
            }
        }
        return $descriptors;
    }

    /**
     * Get the item from an absolute path
     * TODO: should we call this getNode? does not work for property. (see ObjectManager::getPropertyByPath for more on properties)
     * @param path absolute path to item
     * @return array for the node (decoded from json)
     * @throws PHPCR_RepositoryException if now logged in
     */
    public function getItem($path) {
        if ('/' != substr($path, 0, 1)) {
            //sanity check
            throw new PHPCR_RepositoryException("Implementation error: '$path' is not an absolute path");
        }
        $this->checkLogin();
        return $this->getJsonFromBackend(self::GET, $this->workspaceUriRoot . $path . '.0.json');
    }
    /**
     * Get the node path from a JCR uuid
     * @param uuid the id in JCR format
     * @return string path to the node
     * @throws PHPCR_ItemNotFoundException if the backend does not know the uuid
     * @throws PHPCR_RepositoryException if now logged in
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
            throw new PHPCR_RepositoryException('Unexpected answer from server: '.$dom->saveXML());
        }
        $fullPath = $set->item(0)->textContent;
        if (strncmp($this->workspaceUriRoot, $fullPath, strlen($this->workspaceUri))) {
            throw new PHPCR_RepositoryException("Server answered a path that is not in the current workspace: uuid=$uuid, path=$fullPath, workspace=".$this->workspaceUriRoot);
        }
        return substr(substr($fullPath, 0, -1), //cut trailing slash /
                      strlen($this->workspaceUriRoot)); //remove uri, workspace and root node
    }

    /**
     * get the registered namespaces mappings from the backend
     * @return associative array of prefix => uri
     * @throws PHPCR_RepositoryException if now logged in
     */
    public function getNamespaces() {
        $this->checkLogin();

        $dom = $this->getDomFromBackend(self::REPORT, $this->workspaceUri,
                                        self::buildReportRequest('dcr:registerednamespaces'));
        if ($dom->firstChild->localName != 'registerednamespaces-report' ||
            $dom->firstChild->namespaceURI != self::NS_DCR) {
            throw new PHPCR_RepositoryException('Error talking to the backend. '.$dom->saveXML());
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
     * @throws PHPCR_RepositoryException if now logged in
     */
    public function getNodeTypes($nodeTypes = array()) {
        $this->checkLogin();
        
        $dom = $this->getDomFromBackend(
            self::REPORT, $this->workspaceUri . '/jcr:root',
            self::buildNodeTypesRequest($nodeTypes)
        );
        if ($dom->firstChild->localName != 'nodeTypes') {
            throw new PHPCR_RepositoryException('Error talking to the backend. '.$dom->saveXML());
        }
        return $dom;
    }
    
    /**
     * Throws an error if the there is no login
     * @throws PHPCR_RepositoryException if now logged in
     */
    protected function checkLogin() {
        if (empty($this->workspaceUri)) {
            throw new PHPCR_RepositoryException("Implementation error: Please login before accessing content");
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
     * @param string type the http method to use¨
     * @param string uri the uri to request
     * @param string body the body to send as post, default is empty
     * @param int depth How far the request should go, default is 0 (setting the Depth HTTP header)
     */
    protected function prepareRequest($type, $uri, $body = '', $depth = 0) {

        // make sure we have a curl handle
        $this->initConnection();

        if ($this->credentials instanceof PHPCR_SimpleCredentials) {
            $this->curl->setopt(CURLOPT_USERPWD, $this->credentials->getUserID().':'.$this->credentials->getPassword());
        }
        
        $headers = array(
            'Depth: ' . $depth,
            'Content-Type: text/xml; charset=UTF-8',
            'User-Agent: '.self::USER_AGENT
        );

        $this->curl->setopt(CURLOPT_CUSTOMREQUEST, $type);
        $this->curl->setopt(CURLOPT_URL, $uri);
        $this->curl->setopt(CURLOPT_RETURNTRANSFER, 1);
        $this->curl->setopt(CURLOPT_HTTPHEADER, $headers);
        $this->curl->setopt(CURLOPT_POSTFIELDS, $body);
    }
    /**
     * Takes a curl handle prepared by prepareRequest, executes it and checks
     * for transport level errors, throwing the appropriate exceptions.
     * @return raw data string
     * @throws PHPCR_RepositoryExceptions and descendants on connection errors
     */
    protected function getRawFromBackend() {
        $data = $this->curl->exec();
        if (NULL === $data || empty($data)) {
            switch($this->curl->errno()) {
                case CURLE_COULDNT_RESOLVE_HOST:
                case CURLE_COULDNT_CONNECT:
                    throw new PHPCR_NoSuchWorkspaceException($this->curl->error());
                default:
                    if ($data == '') {
                        $msg = 'No data returned by server.';
                    } else {
                        $msg = $this->curl->error();
                        if ($msg == '') $msg = 'No reason given by curl.';
                    }
                    throw new PHPCR_RepositoryException($msg);
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
     * @throws PHPCR_RepositoryException
     * @throws PHPCR_NoSuchWorkspaceException
     */
    protected function getDomFromBackend($type, $uri, $body='', $depth=0) {
        //OPTIMIZE: re-use connection. JACK-7
        $this->prepareRequest($type, $uri, $body, $depth);
        $xml = $this->getRawFromBackend();
        $dom = new DOMDocument();
        $dom->loadXML($xml);

        $err = $dom->getElementsByTagNameNS(self::NS_DCR, 'exception');
        if ($err->length > 0) {
            //TODO: can we trust jackrabbit to always have an exception node if status is not OK?
            $status = $this->curl->getinfo();
            $err = $err->item(0);
            $errClass = $err->getElementsByTagNameNS(self::NS_DCR, 'class')->item(0)->textContent;
            $errMsg = $err->getElementsByTagNameNS(self::NS_DCR, 'message')->item(0)->textContent;
            switch($errClass) {
                case 'javax.jcr.NoSuchWorkspaceException':
                    throw new PHPCR_NoSuchWorkspaceException('HTTP '.$status['http_code'] . ": $errMsg");
                case 'javax.jcr.nodetype.NoSuchNodeTypeException':
                    throw new PHPCR_NodeType_NoSuchNodeTypeException('HTTP '.$status['http_code'] . ": $errMsg");
                case 'javax.jcr.ItemNotFoundException':
                    throw new PHPCR_ItemNotFoundException('HTTP '.$status['http_code'] . ": $errMsg");

                //TODO: map more errors here?
                default:
                    throw new PHPCR_RepositoryException('HTTP '.$status['http_code'] . ": $errMsg ($errClass)");
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
     * @throws PHPCR_ItemNotFoundException if the response is not valid
     * @throws PHPCR_RepositoryException
     */
    protected function getJsonFromBackend($type, $uri, $body='', $depth=0) {
        //OPTIMIZE: re-use connection. JACK-7
        $this->prepareRequest($type, $uri, $body, $depth);
        $jsonstring = $this->getRawFromBackend();
        $json = json_decode($jsonstring);
        if (! is_object($json)) {
            $status = $this->curl->getinfo();
            if (404 === $status['http_code']) {
                throw new PHPCR_ItemNotFoundException('Path not found: ' . $uri);
            } elseif (500 <= $status['http_code']) {
                throw new PHPCR_RepositoryException("Error from backend for '$type' '$uri'\n$jsonstring");
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
                throw new PHPCR_RepositoryException("Not a valid json object. '$jsonstring' ('$type'  '$uri')");
            }
        }
        //TODO: are there error responses in json format? if so, handle them
        return $json;
    }
}
