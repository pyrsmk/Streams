<?php

require('_base.php');

runStream(function() {
    return new Streams\GooglePlus\People('+frandroid', [
        'api' => 'AIzaSyAlKfOvbX_fJG7fNR7_H3N5HW8teFI_GO0',
        'limit' => 100
    ]);
});