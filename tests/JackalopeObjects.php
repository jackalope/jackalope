<?php
require_once(dirname(__FILE__) . '/inc/baseSuite.php');
require_once(dirname(__FILE__) . '/JackalopeObjects/Repository.php');
require_once(dirname(__FILE__) . '/JackalopeObjects/Session.php');
require_once(dirname(__FILE__) . '/JackalopeObjects/Workspace.php');
require_once(dirname(__FILE__) . '/JackalopeObjects/Node.php');
require_once(dirname(__FILE__) . '/JackalopeObjects/Value.php');

/** Test constructors and other implementation specific methods.
 *  Normal API compliance is tested by the API tests.
 */
class jackalope_tests_JackalopeObjects extends jackalope_baseSuite {

    public static function suite() {
        $suite = new jackalope_tests_JackalopeObjects('JackalopeObjects');
        $suite->addTestSuite('jackalope_tests_Repository');
        $suite->addTestSuite('jackalope_tests_Session');
        $suite->addTestSuite('jackalope_tests_Workspace');
        $suite->addTestSuite('jackalope_tests_Node');
        $suite->addTestSuite('jackalope_tests_Value');
        return $suite;
    }
}
