<?php

namespace jackalope\tests;

require_once(dirname(__FILE__) . '/inc/baseSuite.php');
require_once(dirname(__FILE__) . '/JackalopeObjects/Repository.php');
require_once(dirname(__FILE__) . '/JackalopeObjects/Session.php');
require_once(dirname(__FILE__) . '/JackalopeObjects/Workspace.php');
require_once(dirname(__FILE__) . '/JackalopeObjects/Node.php');
require_once(dirname(__FILE__) . '/JackalopeObjects/Value.php');
require_once(dirname(__FILE__) . '/JackalopeObjects/NodeTypeManager.php');

/** Test constructors and other implementation specific methods.
 *  Normal API compliance is tested by the API tests.
 */
class JackalopeObjects extends \jackalope\baseSuite {

    public static function suite() {
        $suite = new JackalopeObjects('JackalopeObjects');
        $suite->addTestSuite('\jackalope\tests\JackalopeObjects\Repository');
        $suite->addTestSuite('\jackalope\tests\JackalopeObjects\Session');
        $suite->addTestSuite('\jackalope\tests\JackalopeObjects\Workspace');
        $suite->addTestSuite('\jackalope\tests\JackalopeObjects\Node');
        $suite->addTestSuite('\jackalope\tests\JackalopeObjects\Value');
        $suite->addTestSuite('\jackalope\tests\JackalopeObjects\NodeTypeManager');
        return $suite;
    }
}
