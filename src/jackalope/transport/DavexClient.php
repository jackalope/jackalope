<?php

/**
 * Connection to one server.
 * Once the login method has been called, the workspace is set and can not be changed anymore.
 */
class jackalope_transport_DavexClient implements jackalope_TransportInterface {
    protected $curl;
    protected $server;
    protected $workspace;
    protected $credentials = false;
    const USER_AGENT = 'jackalope-php/1.0';
    const NS_DCR = 'http://www.day.com/jcr/webdav/1.0';
    const REPOSITORY_DESCRIPTORS = '<?xml version="1.0" encoding="UTF-8"?><dcr:repositorydescriptors xmlns:dcr="http://www.day.com/jcr/webdav/1.0"/>';
    const WORKSPACE_NAME = '<?xml version="1.0" encoding="UTF-8"?><D:propfind xmlns:D="DAV:"><D:prop><dcr:workspaceName xmlns:dcr="http://www.day.com/jcr/webdav/1.0"/><D:workspace/></D:prop></D:propfind>';

    /** Create a transport pointing to a server url.
     *  @param serverUri location of the server
     */
    public function __construct($serverUri) {
        $this->curl = curl_init();
        $this->server = $serverUri;
    }

    /**
     * Set this transport to a specific credential and a workspace.
     * This can only be called once. To connect to another workspace or with
     * another credential, use a fresh instance of transport.
     *
     * @param credentials A PHPCR_SimpleCredentials instance (this is the only type currently understood)
     * @param workspaceName The workspace name for this transport.
     * @throws PHPCR_LoginException if authentication or authorization (for the specified workspace) fails
     * @throws PHPCR_NoSuchWorkspacexception if the specified workspaceName is not recognized
     * @throws PHPCR_RepositoryException if another error occurs
     */
    public function login(PHPCR_CredentialsInterface $credentials, $workspaceName) {
        if ($this->credentials !== false) throw new PHPCR_RepositoryException('Do not call login twice. Rather instantiate a new Transport object to log in as different user or for a different workspace.');

        $this->credentials = $credentials;
        $this->workspace = $workspaceName;
        if ($credentials instanceof PHPCR_SimpleCredentials) {
            curl_setopt($this->curl, CURLOPT_USERPWD,
                        $credentials->getUserID().':'.$credentials->getPassword());
        } else {
            throw new PHPCR_LoginException('Unkown Credentials Type: '.get_class($credentials));
        }

        $headers = array(
            'Depth: 0',
            'Content-Type: text/xml; charset=UTF-8',
            'User-Agent: '.self::USER_AGENT
        );

        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PROPFIND');
        curl_setopt($this->curl, CURLOPT_URL, $this->server . '/' . $this->workspace);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, self::WORKSPACE_NAME);

        $xml = curl_exec($this->curl);

        if ($xml === false) {
            switch(curl_errno($this->curl)) {
                case CURLE_COULDNT_RESOLVE_HOST:
                case CURLE_COULDNT_CONNECT:
                    throw new PHPCR_NoSuchWorkspaceException(curl_error($this->curl));
                default:
                    throw new PHPCR_RepositoryException(curl_error($this->curl));
            }
        }
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $err = $dom->getElementsByTagNameNS(self::NS_DCR, 'exception');
        if ($err->length > 0) {
            $err = $err->item(0);
            $errClass = $err->getElementsByTagNameNS(self::NS_DCR, 'class')->item(0)->textContent;
            $errMsg = $err->getElementsByTagNameNS(self::NS_DCR, 'message')->item(0)->textContent;
            if ($errClass == 'javax.jcr.NoSuchWorkspaceException') {
                throw new PHPCR_NoSuchWorkspaceException($errMsg);
            } else {
                throw new PHPCR_RepositoryException($errMsg);
            }
        }
        $set = $dom->getElementsByTagNameNS(self::NS_DCR, 'workspaceName');
        if ($set->length != 1) {
            throw new PHPCR_RepositoryException('Unexpected answer from server: '.$xml);
        }
        if ($set->item(0)->textContent != $this->workspace) {
            throw new PHPCR_RepositoryException('Wrong workspace in answer from server: '.$xml);
        }
    }

    /**
     * Get the repository descriptors from the jackrabbit server
     * This happens without login or accessing a specific workspace.
     *
     * @return Array with name => Value for the descriptors
     * @throws PHPCR_RepositoryException if error occurs
     */
    public function getRepositoryDescriptors() {
        $headers = array(
            'Depth: 0',
            'Content-Type: text/xml; charset=UTF-8',
            'User-Agent: '.self::USER_AGENT
        );
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'REPORT');
        curl_setopt($this->curl, CURLOPT_URL, $this->server);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, self::REPOSITORY_DESCRIPTORS);

        $xml = curl_exec($this->curl);
        if ($xml === false) {
            throw new PHPCR_RepositoryException('fail: '.curl_error($this->curl));
        }
        $dom = new DOMDocument();
        $dom->loadXML($xml);
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
}
