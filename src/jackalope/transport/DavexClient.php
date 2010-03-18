<?php

/**
 * Connection to one server.
 * Once the login method has been called, the workspace is set and can not be changed anymore.
 */
class jackalope_transport_DavexClient implements jackalope_TransportInterface {
    protected $server;
    protected $workspace;
    /** for convenience: "$server/$workspace" */
    protected $workspaceUri;
    protected $credentials = false;

    const USER_AGENT = 'jackalope-php/1.0';
    const NS_DCR = 'http://www.day.com/jcr/webdav/1.0';
    const REGISTERED_NAMESPACES = '<?xml version="1.0" encoding="UTF-8"?>< xmlns:dcr="http://www.day.com/jcr/webdav/1.0"/>';

    const GET = 'GET';
    const REPORT = 'REPORT';
    const PROPFIND = 'PROPFIND';

    /** Create a transport pointing to a server url.
     *  @param serverUri location of the server
     */
    public function __construct($serverUri) {
        $this->server = $serverUri;
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
        $this->workspaceUri = $this->server . '/' . $workspaceName;

        $dom = $this->getDomFromBackend(self::PROPFIND,
                                        $this->server . '/' . $this->workspace,
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
     * @param path absolute path to item
     */
    public function getItem($path) {
        if ('/' != substr($path, 0, 1)) {
            //sanity check
            throw new PHPCR_RepositoryException("Implementation error: '$path' is not an absolute path");
        }
        if (empty($this->workspaceUri)) {
            throw new PHPCR_RepositoryException("Implementation error: Please login before accessing content");
        }
        $path = $this->workspaceUri . $path;
        if ('/' !== substr($path, -1, 1)) {
            $path .= '/';
        }
        return $this->getJsonFromBackend(self::GET, $path . '.0.json');
    }

    /** get the registered namespaces mappings from the backend
     *  @return associative array of prefix => uri
     */
    public function getNamespaces() {
        if (empty($this->workspaceUri)) {
            throw new PHPCR_RepositoryException("Implementation error: Please login before accessing content");
        }
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

    /**
     * Set the standard parameters for a curl session.
     * If you only use this function, you can do a multi request session
     * without fearing that information from one request messes with the
     * next request.
     *
     * @param handle $curl the curl handler to use
     * @param string the http method to use¨
     * @param string the uri to request
     * @param string the body to send as post, default is empty
     * @param int How far the request should go, default is 0 (setting the Depth HTTP header)
     * @return the curl handle passed to the method
     */
    protected function prepareRequest($curl, $type, $uri, $body = '', $depth = 0) {
        if ($this->credentials instanceof PHPCR_SimpleCredentials) {
            curl_setopt($curl, CURLOPT_USERPWD,
                        $this->credentials->getUserID().':'.$this->credentials->getPassword());
        }
        $headers = array(
            'Depth: ' . $depth,
            'Content-Type: text/xml; charset=UTF-8',
            'User-Agent: '.self::USER_AGENT
        );
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $type);
        curl_setopt($curl, CURLOPT_URL, $uri);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        return $curl;
    }
    /**
     * Takes a curl handle prepared by prepareRequest, executes it and checks
     * for transport level errors, throwing the appropriate exceptions.
     * @return raw data string
     * @throws PHPCR_RepositoryExceptions and descendants on connection errors
     */
    protected function getRawFromBackend($curl) {
        $data = curl_exec($curl);
        if (NULL === $data || empty($data)) {
            switch(curl_errno($curl)) {
                case CURLE_COULDNT_RESOLVE_HOST:
                case CURLE_COULDNT_CONNECT:
                    throw new PHPCR_NoSuchWorkspaceException(curl_error($curl));
                default:
                    throw new PHPCR_RepositoryException(curl_error($curl));
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
        //TODO: re-use connection. JACK-7
        $curl = curl_init();
        $this->prepareRequest($curl, $type, $uri, $body, $depth);
        $xml = $this->getRawFromBackend($curl);
        $dom = new DOMDocument();
        $dom->loadXML($xml);

        $err = $dom->getElementsByTagNameNS(self::NS_DCR, 'exception');
        if ($err->length > 0) {
            $err = $err->item(0);
            $errClass = $err->getElementsByTagNameNS(self::NS_DCR, 'class')->item(0)->textContent;
            $errMsg = $err->getElementsByTagNameNS(self::NS_DCR, 'message')->item(0)->textContent;
            switch($errClass) {
                case 'javax.jcr.NoSuchWorkspaceException':
                    throw new PHPCR_NoSuchWorkspaceException($errMsg);
                //TODO: map more errors here
                default:
                    throw new PHPCR_RepositoryException("$errMsg ($errClass)");
            }
        }
        return $dom;
    }

    /**
     * Returns a DOMDocument from the backend or throws exception
     * Does error handling for both connection errors and json problems
     *
     * @param curl The curl handle you want to fetch from
     * @return DOMDocument the loaded XML
     * @throws PHPCR_RepositoryException
     */
    protected function getJsonFromBackend($type, $uri, $body='', $depth=0) {
        //TODO: re-use connection. JACK-7
        $curl = curl_init();
        $this->prepareRequest($curl, $type, $uri, $body, $depth);
        $jsonstring = $this->getRawFromBackend($curl);
        $json = json_decode($jsonstring);
        if (! is_object($json)) {
            throw new PHPCR_RepositoryException("Not a valid json object. '$jsonstring'");
        }
        //TODO: are there error responses in json format? if so, handle them
        return $json;
    }
}
