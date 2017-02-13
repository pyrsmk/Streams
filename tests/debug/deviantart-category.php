<?php

require('_base.php');

runStream(function() {
    return new Streams\DeviantArt\Category('photography/nature', [
        'type' => 'popular8h',
        'api' => '5788',
        'secret' => '61321fe46c1186e534773f634057d797',
        'nsfw' => false,
        //'limit' => 50
    ]);
});