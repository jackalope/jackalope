<?php

class jackalope_transport_DavexClient implements jackalope_TransportInterface {
    protected $curl;
    protected $server;
    protected $workspace;
    const USER_AGENT = 'jackalope-php/1.0';
    const NS_DCR = 'http://www.day.com/jcr/webdav/1.0';
    const REPOSITORY_DESCRIPTORS = '<?xml version="1.0" encoding="UTF-8"?><dcr:repositorydescriptors xmlns:dcr="http://www.day.com/jcr/webdav/1.0"/>';
    const WORKSPACE_NAME = '<?xml version="1.0" encoding="UTF-8"?><D:propfind xmlns:D="DAV:"><D:prop><dcr:workspaceName xmlns:dcr="http://www.day.com/jcr/webdav/1.0"/><D:workspace/></D:prop></D:propfind>';

    /** Create a new transport with username / password for the server
     * @throws PHPCR_LoginException if authentication or authorization (for the specified workspace) fails
     * @throws PHPCR_NoSuchWorkspacexception if the specified workspaceName is not recognized
     * @throws PHPCR_RepositoryException if another error occurs
     */
    public function __construct(PHPCR_CredentialsInterface $credentials, $serverUri, $workspaceName) {
        $this->curl = curl_init();
        $this->server = $serverUri;
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
        curl_setopt($this->curl, CURLOPT_URL, $serverUri . '/' . $this->workspace);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, self::WORKSPACE_NAME);

        $xml = curl_exec($this->curl);

        if ($xml === false) {var_dump(curl_errno($this->curl));
            switch(curl_errno($this->curl)) {
                //case CURLE_LOGIN_DENIED:
                //    throw new PHPCR_LoginException(curl_error($this->curl));
                case CURLE_COULDNT_RESOLVE_HOST:
                case CURLE_COULDNT_CONNECT:
                    throw new PHPCR_NoSuchWorkspaceException(curl_error($this->curl));
                default:
                    throw new PHPCR_RepositoryException(curl_error($this->curl));
            }
        }
        var_dump($xml);
        $dom = DOMDocument::loadXML($xml);
        $set = $dom->getElementsByTagNameNS(self::NS_DCR, 'workspaceName');
        if ($set->length != 1) {
            throw new PHPCR_RepositoryException('Invalid answer from server: '.$xml);
        }
        if ($set->item(0)->textContent != $this->workspace) {
            throw new PHPCR_RepositoryException('Invalid answer from server: '.$xml);
        }
    }

    /**
     * Get the repository descriptors from the jackrabbit server
     * This happens without login or accessing a specific workspace.
     *
     * @return Array with name => Value for the descriptors
     * @throws PHPCR_RepositoryException if error occurs
     */
    public static function getRepositoryDescriptors($serverUri) {
        $curl = curl_init();
        $headers = array(
            'Depth: 0',
            'Content-Type: text/xml; charset=UTF-8',
            'User-Agent: '.self::USER_AGENT
        );
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'REPORT');
        curl_setopt($curl, CURLOPT_URL, $serverUri);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, self::REPOSITORY_DESCRIPTORS);

        $xml = curl_exec($curl);
        if ($xml === false) {
            throw new PHPCR_RepositoryException('fail: '.curl_error($curl));
        }
        $dom = DOMDocument::loadXML($xml);
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
