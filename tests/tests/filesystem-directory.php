<?php

$suite = new MiniSuite\Suite('FileSystem\Directory');

$suite->hydrate(function($suite) {
    $suite['config'] = [
        'limit' => 2
    ];
});

$suite->expects("limit: 2")
      ->that(function($suite) {
            $elements = [];
            $stream = new Streams\FileSystem\Directory(__DIR__.'/../medias/', $suite['config']);
            $stream->get()->then(function($data) use(&$elements) {
                $elements = $data;
            })->wait();
            verifyConsistency($elements);
            return count($elements);
      })
      ->equals(2);

$suite->expects("limit: false")
      ->that(function($suite) {
            $elements = [];
            $config = $suite['config'];
            $config['limit'] = false;
            $stream = new Streams\FileSystem\Directory(__DIR__.'/../medias/', $config);
            $stream->get()->then(function($data) use(&$elements) {
                $elements = $data;
            })->wait();
            verifyConsistency($elements);
            return count($elements);
      })
      ->isGreaterThan(2);
