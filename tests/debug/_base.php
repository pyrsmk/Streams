<?php

require('../../vendor/autoload.php');
require('../vendor/autoload.php');

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
set_time_limit(0);

function runStream($getStream) {
    $t = microtime(true);
    $getStream()->getElements()->then(function($elements) use($t) {
        echo 'Benchmark : ',round(microtime(true) - $t, 2),'s';
        debug($elements);
    })->wait();
}