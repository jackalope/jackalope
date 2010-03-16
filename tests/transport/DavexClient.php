<?php
require_once(dirname(__FILE__) . '/../inc/baseCase.php');

class jackalope_tests_transport_DavexClient extends jackalope_baseCase {
    public function testGetRepositoryDescriptors() {
        $d = new jackalope_transport_DavexClient('http://localhost:8080'); //TODO: put this in base setup
        $d->getRepositoryDescriptors();
    }
}
