<?php

$suite = new MiniSuite\Suite('GooglePlus\People');

$suite->hydrate(function($suite) {
    $suite['config'] = [
        'api' => 'AIzaSyAlKfOvbX_fJG7fNR7_H3N5HW8teFI_GO0',
        'nsfw' => false,
        'limit' => 10
    ];
});

$suite->expects("limit: 10")
      ->that(function($suite) {
            $elements = [];
            $stream = new Streams\GooglePlus\People('+frandroid', $suite['config']);
            $stream->getElements()->then(function($data) use(&$elements) {
                $elements = $data;
            })->wait();
            verifyConsistency($elements);
            return count($elements);
      })
      ->equals(10);

$suite->expects("limit: 100")
      ->that(function($suite) {
            $elements = [];
            $config = $suite['config'];
            $config['limit'] = 100;
            $stream = new Streams\GooglePlus\People('+frandroid', $config);
            $stream->getElements()->then(function($data) use(&$elements) {
                $elements = $data;
            })->wait();
            verifyConsistency($elements);
            return count($elements);
      })
      ->equals(100);
