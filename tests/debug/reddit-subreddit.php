<?php

require('_base.php');

runStream(function() {
    return new Streams\Reddit\Subreddit('videos', [
        'type' => 'new',
        'nsfw' => false,
        'limit' => 100
    ]);
});