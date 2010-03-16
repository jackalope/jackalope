<?php
require_once(dirname(__FILE__) . '/inc/baseSuite.php');
require_once(dirname(__FILE__) . '/JackalopeObjects/Value.php');

class jackalope_tests_JackalopeObjects extends jackalope_baseSuite {

    public static function suite() {
        $suite = new jackalope_tests_JackalopeObjects('JackalopeObjects');
        $suite->addTestSuite('jackalope_tests_Value');
        return $suite;
    }
}
