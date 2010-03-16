<?php
require_once(dirname(__FILE__) . '/../inc/baseCase.php');

class jackalope_tests_transport_DavexClient extends jackalope_baseCase {
    public function testGetRepositoryDescriptors() {
        $desc = jackalope_transport_DavexClient::getRepositoryDescriptors($this->config['url']);
        $this->assertType('array', $desc);
        foreach($desc as $key => $value) {
            $this->assertType('string', $key);
            if (is_array($value)) {
                foreach($value as $val) {
                    $this->assertType('PHPCR_ValueInterface', $val);
                }
            } else {
                $this->assertType('PHPCR_ValueInterface', $value);
            }
        }
    }
    /**
     * @expectedException PHPCR_RepositoryException
     */
    public function testGetRepositoryDescriptorsNoserver() {
        $d = jackalope_transport_DavexClient::getRepositoryDescriptors('http://localhost:1/server');
    }

    public function testLogin() {
        $d = new jackalope_transport_DavexClient($this->credentials, $this->config['url'], $this->config['workspace']);
    }
    /**
     * @expectedException PHPCR_NoSuchWorkspaceException
     */
    public function testLoginNoServer() {
        $d = new jackalope_transport_DavexClient($this->credentials, 'http://localhost:1/server', $this->config['workspace']);
    }
    /**
     * @expectedException PHPCR_NoSuchWorkspaceException
     */
    public function testLoginNoSuchWorkspace() {
        $d = new jackalope_transport_DavexClient($this->credentials, $this->config['url'], 'non-existing-workspace');
    }
    /**
     * @expectedException PHPCR_LoginException
     */
    public function testLoginInvalidPw() {
        $d = new jackalope_transport_DavexClient(new PHPCR_SimpleCredentials('nosuch', 'user'), $this->config['url'], $this->config['workspace']);
    }
}
