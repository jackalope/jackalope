<?php

/** make sure we get ALL infos from php */
error_reporting(E_ALL | E_STRICT);

/**
 * Bootstrap file for jackalope
 */

if (!$loader = @include __DIR__.'/../vendor/autoload.php') {
    die('You must set up the project dependencies, run the following commands:'.PHP_EOL.
        'curl -s http://getcomposer.org/installer | php'.PHP_EOL.
        'php composer.phar install'.PHP_EOL);
}

$loader->add('Jackalope', ['tests']);
