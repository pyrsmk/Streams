<?php

require('_base.php');

runStream(function() {
    return new Streams\Reddit\User('hansiphoto', [
        'nsfw' => false,
        'limit' => 100
    ]);
});