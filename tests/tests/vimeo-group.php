<?php

$suite = new MiniSuite\Suite('Vimeo\Group');

$suite->hydrate(function($suite) {
    $suite['config'] = [
        'api' => '0a35200c250631d1797c6c7dcf1d91b76af0a81c',
        'secret' => 'GPaIc3bVs/saZb9Y2zT6Vw2JE8klpbYdWGly4Rqggod0YJYh5spY5EKUJiPci3kt7w67GSgOM1J5j+/5sr80lcBtySF2+U/IAUbR5dhrhN4Jx/5x42jCZC7ivj3+nOIP',
        'nsfw' => false,
        'limit' => 10
    ];
});

$suite->expects("limit: 10")
      ->that(function($suite) {
            $elements = [];
            $stream = new Streams\Vimeo\Group('animation', $suite['config']);
            $stream->get()->then(function($data) use(&$elements) {
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
            $stream = new Streams\Vimeo\Group('animation', $config);
            $stream->get()->then(function($data) use(&$elements) {
                $elements = $data;
            })->wait();
            verifyConsistency($elements);
            return count($elements);
      })
      ->equals(100);

$suite->expects("nsfw: true")
      ->that(function($suite) {
            $elements = [];
            $config = $suite['config'];
            $config['nsfw'] = true;
            $stream = new Streams\Vimeo\Group('307812', $config);
            $stream->get()->then(function($data) use(&$elements) {
                $elements = $data;
            })->wait();
            verifyConsistency($elements);
            return count($elements);
      })
      ->equals(10);

$suite->expects("nsfw: false")
      ->that(function($suite) {
            $elements = [];
            $stream = new Streams\Vimeo\Group('307812', $suite['config']);
            $stream->get()->then(function($data) use(&$elements) {
                $elements = $data;
            })->wait();
            verifyConsistency($elements);
            return count($elements);
      })
      ->equals(0);
