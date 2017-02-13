<?php

require('_base.php');

runStream(function() {
    return new Streams\DeviantArt\Gallery('aceofspades762', [
        'api' => '5788',
        'secret' => '61321fe46c1186e534773f634057d797',
        'nsfw' => false,
        'limit' => 50
    ]);
});