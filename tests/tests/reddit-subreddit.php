<?php

$suite = new MiniSuite\Suite('Reddit\Subreddit');

$suite->hydrate(function($suite) {
    $suite['config'] = [
        'type' => 'new',
        'nsfw' => false,
        'limit' => 10
    ];
});

$types = ['popular', 'new', 'rising', 'controversial', 'top', 'gilded'];

foreach($types as $type) {
    $suite->expects("type: $type")
          ->that(function($suite) use($type) {
                $elements = [];
                $config = $suite['config'];
                $config['type'] = $type;
                $stream = new Streams\Reddit\Subreddit('earthporn', $config);
                $stream->getElements()->then(function($data) use(&$elements) {
                    $elements = $data;
                })->wait();
                verifyConsistency($elements);
                return count($elements);
          })
          ->isGreaterThan(0);
}

$suite->expects("limit: 100")
      ->that(function($suite) {
            $elements = [];
            $config = $suite['config'];
            $config['limit'] = 100;
            $stream = new Streams\Reddit\Subreddit('earthporn', $config);
            $stream->getElements()->then(function($data) use(&$elements) {
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
            $stream = new Streams\Reddit\Subreddit('gonewild', $config);
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
            $stream = new Streams\Reddit\Subreddit('gonewild', $suite['config']);
            $stream->getElements()->then(function($data) use(&$elements) {
                $elements = $data;
            })->wait();
            verifyConsistency($elements);
            return count($elements);
      })
      ->equals(0);
