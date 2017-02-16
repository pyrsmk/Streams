<?php

$suite = new MiniSuite\Suite('Facebook\Videos');

$suite->hydrate(function($suite) {
    $suite['config'] = [
        'api' => '1112696305414937',
        'secret' => '36de47f690026dc3c0d5f9ab0a6f041d',
        'limit' => 5
    ];
});

$suite->expects("limit: 5")
      ->that(function($suite) {
            $elements = [];
            $stream = new Streams\Facebook\Videos('ChatNoirDesign', $suite['config']);
            $stream->getElements()->then(function($data) use(&$elements) {
                $elements = $data;
            })->wait();
            verifyConsistency($elements);
            return count($elements);
      })
      ->equals(5);

$suite->expects("limit: false")
      ->that(function($suite) {
            $elements = [];
            $config = $suite['config'];
            $config['limit'] = false;
            $stream = new Streams\Facebook\Videos('ChatNoirDesign', $config);
            $stream->getElements()->then(function($data) use(&$elements) {
                $elements = $data;
            })->wait();
            verifyConsistency($elements);
            return count($elements);
      })
      ->isGreaterThan(5);
