<?php
require_once(dirname(__FILE__) . '/inc/baseSuite.php');
require_once(dirname(__FILE__) . '/transport/DavexClient.php');

class jackalope_tests_Transport extends jackalope_baseSuite {

    public static function suite() {
        $suite = new jackalope_tests_Transport('Transport');
        $suite->addTestSuite('jackalope_tests_transport_DavexClient');
        return $suite;
    }
}

