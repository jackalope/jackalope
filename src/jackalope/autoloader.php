<?php
/*
 * Autoloader takes care of loading classes only when they are required.
 * If your project does not provide its own autoloader, you can require()
 * this file in your entry points.
 */

/** load a class named $class */
function jackalope_autoloader($class) {
    if (false !== ($pos = strripos($class, '\\')) && 0 === strpos($class, 'jackalope')) {
        // namespaced class name
        $namespace = substr($class, 0, $pos);
        $class = substr($class, $pos + 1);
        $file = __DIR__.'/../'.str_replace('\\', DIRECTORY_SEPARATOR, $namespace).DIRECTORY_SEPARATOR.str_replace('_', DIRECTORY_SEPARATOR, $class).'.php';
        if (file_exists($file)) {
            require $file;
        }
        return;
    } else if (0 === strpos($class, 'PHPCR_')) {
        $incFile = dirname(__FILE__) . '/../../lib/' . str_replace("_", DIRECTORY_SEPARATOR, $class).".php";
        if (@fopen($incFile, "r", TRUE)) {
            include($incFile);
            return $incFile;
        }
    } else if (0 === strpos($class, 'jackalope_')) {
        //Hardcoded length of jackalope_
        $incFile = dirname(__FILE__) . '/' . str_replace("_", DIRECTORY_SEPARATOR, substr($class, 9)) . ".php";
        if (@fopen($incFile, "r", TRUE)) {
            include($incFile);
            return $incFile;
        }
    }
    return FALSE;
}
spl_autoload_register('jackalope_autoloader');
