<?php
require_once(dirname(__FILE__) . '/../inc/baseCase.php');

class jackalope_tests_Repository extends jackalope_baseCase {
    public function testConstructor() {
        $credentials = new PHPCR_SimpleCredentials('test', 'cred');
        $workspaceName = 'sadf3sd';
        $transport = $this->getMock('jackalope_transport_DavexClient', array('login', 'getRepositoryDescriptors'), array('http://test'));
        $transport->expects($this->once())
            ->method('login')
            ->with($this->equalTo($credentials), $this->equalTo($workspaceName))
            ->will($this->returnValue(true));
        $transport->expects($this->once())
            ->method('getRepositoryDescriptors')
            ->will($this->returnValue(array('bla', 'bli')));

        $repo = new jackalope_Repository(null, $transport);
        $session = $repo->login($credentials, $workspaceName);
        $this->assertType('jackalope_Session', $session);
    }
    //descriptors are tested by jackalope-api-tests AccessTest/RepositoryDescriptors.php
}

class jackalope_tests_RepositoryMockTransport implements jackalope_TransportInterface {
    private $cred;
    private $workspace;

    function __construct($cred, $workspace) {
        $this->cred = $cred;
        $this->workspace = $workspace;
    }
    public function login(PHPCR_CredentialsInterface $credentials, $workspaceName) {
        if ($this->cred !== $credentials) throw new Exception('credentials mismatch');
        if ($this->workspace != $workspaceName) throw new Exception('workspace name mismatch');
        return true;
    }
    public function getRepositoryDescriptors() {
        return array();
    }
}