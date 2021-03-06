<?php

$suite = new MiniSuite\Suite('Flickr\Album');

$suite->hydrate(function($suite) {
    $suite['config'] = [
        'api' => '6f7073b4b647bb19088a7bae5189421d',
        'limit' => 10
    ];
});

$suite->expects("limit: 10")
      ->that(function($suite) {
            $elements = [];
            $stream = new Streams\Flickr\Album('cannon_s5_is/72157625103228853', $suite['config']);
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
            $stream = new Streams\Flickr\Album('cannon_s5_is/72157625103228853', $config);
            $stream->get()->then(function($data) use(&$elements) {
                $elements = $data;
            })->wait();
            verifyConsistency($elements);
            return count($elements);
      })
      ->isGreaterThan(10);
