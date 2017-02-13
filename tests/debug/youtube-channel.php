<?php

require('_base.php');

runStream(function() {
    return new Streams\Youtube\Channel('UCCMxHHciWRBBouzk-PGzmtQ', [
        'api' => 'AIzaSyAlKfOvbX_fJG7fNR7_H3N5HW8teFI_GO0',
        'nsfw' => false,
        'limit' => 100
    ]);
});