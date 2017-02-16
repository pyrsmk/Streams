<?php

require('_base.php');

runStream(function() {
    return new Streams\Youtube\Playlist('PLKipY1cRnemLDJtrSTC6aYv-j6SvRgNNe', [
        'api' => 'AIzaSyAlKfOvbX_fJG7fNR7_H3N5HW8teFI_GO0',
        'limit' => 100
    ]);
});