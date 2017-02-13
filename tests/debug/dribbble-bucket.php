<?php

require('_base.php');

runStream(function() {
    return new Streams\Dribbble\Bucket('476346-Usabilty-examples', [
        'token' => '6001fa7b14dcb624bcc284709c6bde0aef723a9952b9e94da058f56f1f56a37b',
        'nsfw' => false,
        'limit' => 100
    ]);
});