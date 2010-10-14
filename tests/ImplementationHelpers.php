<?php
namespace jackalope\tests;

require_once(dirname(__FILE__) . '/inc/baseSuite.php');
require_once(dirname(__FILE__) . '/ImplementationHelpers/DavexClient.php');
require_once(dirname(__FILE__) . '/ImplementationHelpers/ObjectManager.php');

/** test suite for implementation specific helper classes that do not implement
 *  PHPCR interfaces
 */
class ImplementationHelpers extends \jackalope\baseSuite {

    public static function suite() {
        $suite = new ImplementationHelpers('ImplementationHelpers');
        $suite->addTestSuite('\jackalope\tests\ImplementationHelpers\DavexClient');
        $suite->addTestSuite('\jackalope\tests\ImplementationHelpers\ObjectManager');
        return $suite;
    }
}

