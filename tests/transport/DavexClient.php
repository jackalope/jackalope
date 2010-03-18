<?php
require_once(dirname(__FILE__) . '/../inc/baseCase.php');

class jackalope_tests_transport_DavexClient extends jackalope_baseCase {
    public function testGetRepositoryDescriptors() {
        $t = new jackalope_transport_DavexClient($this->config['url']);
        $desc = $t->getRepositoryDescriptors();
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
        $t = new jackalope_transport_DavexClient('http://localhost:1/server');
        $d = $t->getRepositoryDescriptors();
    }

    public function testLogin() {
        $t = new jackalope_transport_DavexClient($this->config['url']);
        $x = $t->login($this->credentials, $this->config['workspace']);
        $this->assertTrue($x);
    }
    /**
     * @expectedException PHPCR_NoSuchWorkspaceException
     */
    public function testLoginNoServer() {
        $t = new jackalope_transport_DavexClient('http://localhost:1/server');
        $t->login($this->credentials, $this->config['workspace']);
    }
    /**
     * @expectedException PHPCR_NoSuchWorkspaceException
     */
    public function testLoginNoSuchWorkspace() {
        $t = new jackalope_transport_DavexClient($this->config['url']);
        $t->login($this->credentials, 'not-an-existing-workspace');
    }
    /**
     * Should be expectedException PHPCR_LoginException
     */
    public function testLoginInvalidPw() {
        $this->markTestSkipped('make jackrabbit restrict user rights to test this');
        //$d = new jackalope_transport_DavexClient(new PHPCR_SimpleCredentials('nosuch', 'user'), $this->config['url'], $this->config['workspace']);
    }

    public function testGetNamespaces() {
        $t = new jackalope_transport_DavexClient($this->config['url']);
        $x = $t->login($this->credentials, $this->config['workspace']);
        $this->assertTrue($x);
        $ns = $t->getNamespaces();
        $this->assertType('array', $ns);
        foreach($ns as $prefix => $uri) {
            $this->assertType('string', $prefix);
            $this->assertType('string', $uri);
        }
    }
}
