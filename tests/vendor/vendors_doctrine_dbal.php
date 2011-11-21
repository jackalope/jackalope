#!/usr/bin/env php
<?php

set_time_limit(0);

$vendorDir = __DIR__.'/../../lib/vendor';
$deps = array(
    array('doctrine-dbal', 'http://github.com/doctrine/dbal.git', 'origin/master'),
);

foreach ($deps as $dep) {
    list($name, $url, $rev) = $dep;

    echo "> Installing/Updating $name\n";

    $installDir = $vendorDir.'/'.$name;
    if (!is_dir($installDir)) {
        echo "Cloning $name into $installDir\n";
        system(sprintf('git clone -q %s %s', escapeshellarg($url), escapeshellarg($installDir)));
    }

    system(sprintf('cd %s && git fetch -q origin && git reset --hard %s && git submodule update --init', escapeshellarg($installDir), escapeshellarg($rev)));
}
