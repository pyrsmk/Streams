<?php

require('_base.php');

runStream(function() {
    return new Streams\Flickr\User('missinterpretations', [
        'api' => '6f7073b4b647bb19088a7bae5189421d',
        'nsfw' => false,
        'limit' => 100
    ]);
});