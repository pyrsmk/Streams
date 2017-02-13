<?php

require('_base.php');

runStream(function() {
    return new Streams\Flickr\Album('cannon_s5_is/72157625103228853', [
        'api' => '6f7073b4b647bb19088a7bae5189421d',
        'nsfw' => false,
        'limit' => 100
    ]);
});