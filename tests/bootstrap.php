<?php

// PHPUnit 3.4 support
if (method_exists('PHPUnit_Util_Filter', 'addDirectoryToFilter')) {
    require_once 'PHPUnit/Framework.php';
}

require __DIR__.'/../src/Jackalope/autoloader.php';
require __DIR__.'/Jackalope/TestCase.php';
require __DIR__.'/Jackalope/Transport/DoctrineDBAL/DoctrineDBALTestCase.php';
require __DIR__.'/Framework/ProxyObject.php';

if (isset($GLOBALS['phpcr.doctrine.loader']) && is_file($GLOBALS['phpcr.doctrine.loader']) && is_dir($GLOBALS['phpcr.doctrine.dbaldir']) && is_dir($GLOBALS['phpcr.doctrine.commondir'])) {
    require_once($GLOBALS['phpcr.doctrine.loader']);

    $loader = new \Doctrine\Common\ClassLoader("Doctrine\Common", $GLOBALS['phpcr.doctrine.commondir']);
    $loader->register();

    $loader = new \Doctrine\Common\ClassLoader("Doctrine\DBAL", $GLOBALS['phpcr.doctrine.dbaldir']);
    $loader->register();

    $GLOBALS['phpcr.doctrine.loaded'] = true;
}
