<?php

$suite = new MiniSuite\Suite('Facebook\Notes');

$suite->hydrate(function($suite) {
    $suite['config'] = [
        'api' => '1112696305414937',
        'secret' => '36de47f690026dc3c0d5f9ab0a6f041d'
    ];
});

$suite->expects("limit: 2")
      ->that(function($suite) {
            $elements = [];
            $config = $suite['config'];
            $config['limit'] = 2;
            $stream = new Streams\Facebook\Notes('289941984496813', $config);
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
            $stream = new Streams\Facebook\Notes('289941984496813', $config);
            $stream->get()->then(function($data) use(&$elements) {
                $elements = $data;
            })->wait();
            verifyConsistency($elements);
            return count($elements);
      })
      ->equals(4);
