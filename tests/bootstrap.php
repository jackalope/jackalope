<?php

// PHPUnit 3.4 support
if (method_exists('PHPUnit_Util_Filter', 'addDirectoryToFilter')) {
    require_once 'PHPUnit/Framework.php';
}

require __DIR__.'/../src/jackalope/autoloader.php';
require __DIR__.'/Jackalope/TestCase.php';
