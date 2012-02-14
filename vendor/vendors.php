#!/usr/bin/env php
<?php

set_time_limit(0);

if (isset($argv[1])) {
    $_SERVER['OPENPNE_BRANCH'] = $argv[1];
}

$vendorDir = __DIR__;
$deps = array(
    array('OpenPNE', 'http://github.com/houou/HOUOU', isset($_SERVER['OPENPNE_BRANCH']) ? $_SERVER['OPENPNE_BRANCH'] : 'tjm/apitest'),
);

foreach ($deps as $dep) {
    list($name, $url, $rev) = $dep;

    echo "> Installing/Updating $name\n";

    $installDir = $vendorDir.'/'.$name;
    if (!is_dir($installDir)) {
        system(sprintf('git clone -q %s %s', escapeshellarg($url), escapeshellarg($installDir)));
    }

    system(sprintf('cd %s && git fetch -q origin && git checkout %s', escapeshellarg($installDir), escapeshellarg($rev)));

    system(sprintf('mkdir %s/plugins/opSheet2ProfilePlugin ', escapeshellarg($installDir));


    echo sprintf('cd %s && mv apps config README lib test vendor/OpenPNE/plugins/opSheet2ProfilePlugin',escapeshellarg($vendorDir . "/../" ));
    system(sprintf('cd %s && mv apps config README lib test vendor/OpenPNE/plugins/opSheet2ProfilePlugin',escapeshellarg($vendorDir . "/../" )));


}
