<?php

$suite = new MiniSuite\Suite('DeviantArt\Category');

$suite->hydrate(function($suite) {
    $suite['config'] = [
        'api' => '5788',
        'secret' => '61321fe46c1186e534773f634057d797',
        'nsfw' => false,
        'limit' => 10
    ];
    $suite['schema'] = [
        
    ];
});

$types = ['newest', 'hot', 'undiscovered', 'popular', 'popular8h', 'popular24h', 'popular3d', 'popular1w', 'popular1m'];

foreach($types as $type) {
    $suite->expects("type: $type")
          ->that(function($suite) use($type) {
                $elements = [];
                $config = $suite['config'];
                $config['type'] = $type;
                $stream = new Streams\DeviantArt\Category('photography/nature', $config);
                $stream->getElements()->then(function($data) use(&$elements) {
                    $elements = $data;
                })->wait();
                verifyConsistency($elements);
                return count($elements);
          })
          ->equals(10);
}

$suite->expects("limit: false")
      ->that(function($suite) {
            $elements = [];
            $config = $suite['config'];
            $config['limit'] = false;
            $stream = new Streams\DeviantArt\Category('photography/nature', $config);
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
            $stream = new Streams\DeviantArt\Category('photography/people/nude', $config);
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
            $stream = new Streams\DeviantArt\Category('photography/people/nude', $suite['config']);
            $stream->getElements()->then(function($data) use(&$elements) {
                $elements = $data;
            })->wait();
            verifyConsistency($elements);
            return count($elements);
      })
      ->equals(0);
