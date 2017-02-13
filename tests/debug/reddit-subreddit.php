<?php

require('_base.php');

runStream(function() {
    return new Streams\Reddit\Subreddit('earthporn', [
        'type' => 'new',
        'nsfw' => false,
        'limit' => 100
    ]);
});