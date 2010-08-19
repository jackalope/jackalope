<?php
require_once(dirname(__FILE__) . '/inc/baseSuite.php');
require_once(dirname(__FILE__) . '/transport/DavexClient.php');
require_once(dirname(__FILE__) . '/ImplementationHelpers/ObjectManager.php');
require_once(dirname(__FILE__) . '/ImplementationHelpers/Helper.php');

/** test suite for implementation specific helper classes that do not implement
 *  PHPCR interfaces
 */
class jackalope_tests_ImplementationHelpers extends jackalope_baseSuite {

    public static function suite() {
        $suite = new jackalope_tests_ImplementationHelpers('ImplementationHelpers');
        $suite->addTestSuite('jackalope_tests_transport_DavexClient');
        $suite->addTestSuite('jackalope_tests_ObjectManager');
        $suite->addTestSuite('jackalope_tests_Helper');
        return $suite;
    }
}

