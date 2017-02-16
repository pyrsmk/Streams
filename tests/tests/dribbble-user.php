<?php

$suite = new MiniSuite\Suite('Dribbble\User');

$suite->hydrate(function($suite) {
    $suite['config'] = [
        'token' => '6001fa7b14dcb624bcc284709c6bde0aef723a9952b9e94da058f56f1f56a37b',
        'limit' => 10
    ];
});

$suite->expects("limit: 10")
      ->that(function($suite) {
            $elements = [];
            $stream = new Streams\Dribbble\User('BurntToast', $suite['config']);
            $stream->getElements()->then(function($data) use(&$elements) {
                $elements = $data;
            })->wait();
            verifyConsistency($elements);
            return count($elements);
      })
      ->equals(10);

$suite->expects("limit: false")
      ->that(function($suite) {
            $elements = [];
            $config = $suite['config'];
            $config['limit'] = false;
            $stream = new Streams\Dribbble\User('BurntToast', $config);
            $stream->getElements()->then(function($data) use(&$elements) {
                $elements = $data;
            })->wait();
            verifyConsistency($elements);
            return count($elements);
      })
      ->isGreaterThan(10);
