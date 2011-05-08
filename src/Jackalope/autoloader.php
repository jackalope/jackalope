<?php
/**
 * Implementation specific helper:
 * Autoloader takes care of loading classes only when they are required.
 * If your project does not provide its own autoloader, you can require()
 * this file in your entry points. It will automatically register the
 * jackalope_autoloader function with spl_autoload_register
 *
 * load a class named $class
 */
function jackalope_autoloader($class)
{
    if (false !== ($pos = strripos($class, '\\'))) {
        $relpath = false;
        $jackPos = strpos($class, 'Jackalope');
        if ($jackPos === 1 || $jackPos === 0) {
            $relpath = '/../';
            $class = substr($class, $jackPos);
            $pos = $pos - $jackPos;
        } elseif (0 === strpos($class, 'PHPCR')) {
            $relpath = '/../../lib/phpcr/src/';
        }

        if ($relpath) {
            // namespaced class name
            $namespace = substr($class, 0, $pos);
            $class = substr($class, $pos + 1);
            $file = __DIR__.$relpath.str_replace('\\', DIRECTORY_SEPARATOR, $namespace).DIRECTORY_SEPARATOR.$class.'.php';
            if (file_exists($file)) {
                require $file;
            }
            return;
        }
    }
    return false;
}
#spl_autoload_extensions('.php');
spl_autoload_register('jackalope_autoloader');
