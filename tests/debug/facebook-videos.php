<?php

require('_base.php');

runStream(function() {
    return new Streams\Facebook\Videos('ChatNoirDesign', [
        'api' => '1112696305414937',
        'secret' => '36de47f690026dc3c0d5f9ab0a6f041d',
        'nsfw' => false,
        'limit' => 100
    ]);
});