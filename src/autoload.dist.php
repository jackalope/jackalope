<?php

$vendorDir = __DIR__.'/../lib/phpcr-utils/lib/vendor';
require_once $vendorDir.'/Symfony/Component/ClassLoader/UniversalClassLoader.php';

$classLoader = new \Symfony\Component\ClassLoader\UniversalClassLoader();
$classLoader->register();

$classLoader->registerNamespaces(array(
    'Jackalope' => __DIR__.'/',
    'PHPCR'   => array(__DIR__.'/../lib/phpcr-utils/src', __DIR__.'/../lib/phpcr/src'),
    'Symfony\Component\Console' => $vendorDir,
    'Symfony\Component\ClassLoader' => $vendorDir,
));
