<?php

$suite = new MiniSuite\Suite('Facebook\Album');

$suite->hydrate(function($suite) {
    $suite['config'] = [
        'api' => '1112696305414937',
        'secret' => '36de47f690026dc3c0d5f9ab0a6f041d',
        'limit' => 10
    ];
});

$suite->expects("Get album photos")
      ->that(function($suite) {
            $elements = [];
            $stream = new Streams\Facebook\Album('1710763805841434', $suite['config']);
            $stream->get()->then(function($data) use(&$elements) {
                $elements = $data;
            })->wait();
            verifyConsistency($elements);
            return count($elements);
      })
      ->equals(10);

$suite->expects("Get profile photos")
      ->that(function($suite) {
            $elements = [];
            $stream = new Streams\Facebook\Album('ChatNoirDesign', $suite['config']);
            $stream->get()->then(function($data) use(&$elements) {
                $elements = $data;
            })->wait();
            verifyConsistency($elements);
            return count($elements);
      })
      ->equals(4);

$suite->expects("Get uploaded photos")
      ->that(function($suite) {
            $elements = [];
            $config = $suite['config'];
            $config['type'] = 'uploaded';
            $stream = new Streams\Facebook\Album('ChatNoirDesign', $config);
            $stream->get()->then(function($data) use(&$elements) {
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
            $stream = new Streams\Facebook\Album('1710763805841434', $config);
            $stream->get()->then(function($data) use(&$elements) {
                $elements = $data;
            })->wait();
            verifyConsistency($elements);
            return count($elements);
      })
      ->isGreaterThan(10);
