<?php

$suite = new MiniSuite\Suite('FiveHundredPx\Gallery');

$suite->hydrate(function($suite) {
    $suite['config'] = [
        'api' => 'ZIjOPNvwMLOg9HvNPD6pPiRhgZUKom4NBqHjBUac',
        'nsfw' => false,
        'limit' => 10
    ];
});

$suite->expects("limit: 10")
      ->that(function($suite) {
            $elements = [];
            $stream = new Streams\FiveHundredPx\Gallery('ademgider/city-map', $suite['config']);
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
            $stream = new Streams\FiveHundredPx\Gallery('ademgider/city-map', $config);
            $stream->getElements()->then(function($data) use(&$elements) {
                $elements = $data;
            })->wait();
            verifyConsistency($elements);
            return count($elements);
      })
      ->isGreaterThan(10);

$suite->expects("nsfw: true")
      ->that(function($suite) {
            $elements = [];
            $config = $suite['config'];
            $config['nsfw'] = true;
            $stream = new Streams\FiveHundredPx\Gallery('mpgriboff/shibari', $config);
            $stream->getElements()->then(function($data) use(&$elements) {
                $elements = $data;
            })->wait();
            verifyConsistency($elements);
            return count($elements);
      })
      ->equals(10);

$suite->expects("nsfw: false")
      ->that(function($suite) {
            $elements = [];
            $stream = new Streams\FiveHundredPx\Gallery('mpgriboff/shibari', $suite['config']);
            $stream->getElements()->then(function($data) use(&$elements) {
                $elements = $data;
            })->wait();
            verifyConsistency($elements);
            return count($elements);
      })
      ->isLessThan(10);
