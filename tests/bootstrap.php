<?php

// PHPUnit 3.4 support
if (method_exists('PHPUnit_Util_Filter', 'addDirectoryToFilter')) {
    require_once 'PHPUnit/Framework.php';
}

require __DIR__.'/../src/Jackalope/autoloader.php';
require __DIR__.'/Jackalope/TestCase.php';
require __DIR__.'/Framework/ProxyObject.php';

if (isset($GLOBALS['jcr.doctrine.loader']) && is_file($GLOBALS['jcr.doctrine.loader']) && is_dir($GLOBALS['jcr.doctrine.dbaldir']) && is_dir($GLOBALS['jcr.doctrine.commondir'])) {
    require_once($GLOBALS['jcr.doctrine.loader']);

    $loader = new \Doctrine\Common\ClassLoader("Doctrine\Common", $GLOBALS['jcr.doctrine.commondir']);
    $loader->register();
    
    $loader = new \Doctrine\Common\ClassLoader("Doctrine\DBAL", $GLOBALS['jcr.doctrine.dbaldir']);
    $loader->register();

    $GLOBALS['jcr.doctrine.loaded'] = true;
}
