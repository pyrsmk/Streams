<?php

require('_base.php');

runStream(function() {
    return new Streams\Facebook\Notes('289941984496813', [
        'api' => '1112696305414937',
        'secret' => '36de47f690026dc3c0d5f9ab0a6f041d',
        'limit' => 10
    ]);
});