<?php

$suite = new MiniSuite\Suite('Youtube\Playlist');

$suite->hydrate(function($suite) {
    $suite['config'] = [
        'api' => 'AIzaSyAlKfOvbX_fJG7fNR7_H3N5HW8teFI_GO0',
        'limit' => 10
    ];
});

$suite->expects("limit: 10")
      ->that(function($suite) {
            $elements = [];
            $stream = new Streams\Youtube\Playlist('PLWmL9Ldoef0sKB07aXA1ekfyIqu-47rV6', $suite['config']);
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
            $stream = new Streams\Youtube\Playlist('PLWmL9Ldoef0sKB07aXA1ekfyIqu-47rV6', $config);
            $stream->get()->then(function($data) use(&$elements) {
                $elements = $data;
            })->wait();
            verifyConsistency($elements);
            return count($elements);
      })
      ->isGreaterThan(10);
