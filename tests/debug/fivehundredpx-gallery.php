<?php

require('_base.php');

runStream(function() {
    return new Streams\FiveHundredPx\Gallery('ademgider/city-map', [
        'api' => 'ZIjOPNvwMLOg9HvNPD6pPiRhgZUKom4NBqHjBUac',
        'nsfw' => false,
        'limit' => 100
    ]);
});