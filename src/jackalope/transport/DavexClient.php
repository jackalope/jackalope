<?php

class jackalope_transport_DavexClient implements jackalope_TransportInterface {
    protected $curl;
    protected $uri;
    private $DESCRIPTORS = '<?xml version="1.0" encoding="UTF-8"?><dcr:repositorydescriptors xmlns:dcr="http://www.day.com/jcr/webdav/1.0"/>';

    public function __construct($serverUri) {
        $this->curl = curl_init();
        $this->uri = $serverUri;
    }

    public function getRepositoryDescriptors() {
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'REPORT');
        curl_setopt($this->curl, CURLOPT_URL, $this->uri . '/server/');
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Depth'=>0));
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->DESCRIPTORS);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        $xml = curl_exec($this->curl);

        var_dump($xml);
        echo curl_error($this->curl);
/*
Depth: 0
User-Agent: Jakarta Commons-HttpClient/3.0
Host: localhost:8080
Content-Length: 112
Content-Type: text/xml; charset=UTF-8
*/
    }

    public function login(PHPCR_CredentialsInterface $credentials, $workspaceName=null) {
        if ($credentials instanceof PHPCR_SimpleCredentials) {
            curl_setopt($this->curl, CURLOPT_USERPWD,
                        $credentials->getUserID().':'.$credentials->getPassword());
        }

    }
}
